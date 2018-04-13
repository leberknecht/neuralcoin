<?php

namespace NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OrderBook;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\SourceInput;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Entity\TradeData;
use DataModelBundle\OutputType\HasDroppedByOutputType;
use DataModelBundle\OutputType\HasRaisedByOutputType;
use DataModelBundle\OutputType\PredictOnePriceOutputType;
use DataModelBundle\Repository\OrderBookRepository;
use DataModelBundle\Repository\SymbolRepository;
use DataModelBundle\Service\BaseService;
use Doctrine\Common\Collections\ArrayCollection;

class NetworkDataService extends BaseService
{
    /**
     * @var SymbolRepository
     */
    private $symbolRepository;
    /**
     * @var OrderBookRepository
     */
    private $orderBookRepository;

    public function __construct(
        SymbolRepository $tradeRepository,
        OrderBookRepository $orderBookRepository
    ) {
        $this->symbolRepository = $tradeRepository;
        $this->orderBookRepository = $orderBookRepository;
    }

    /**
     * @param Network $network
     * @param bool $preview
     * @param \DateTime|null $targetDate
     * @param Symbol|null $symbolOverwrite
     * @return array|Symbol[]
     * @throws \Exception
     */
    public function prepareSymbols(Network $network, $preview = false, \DateTime $targetDate = null, Symbol $symbolOverwrite = null)
    {
        if ($network->isUseMostActiveSymbols()) {
            $network->setSymbols(new ArrayCollection(
                $this->symbolRepository->findMostActive($network->getMostActiveSymbolsCount(), $network->getExchange())
            ));
        }
        if ($preview) {
            $symbols = $this->symbolRepository->getSymbolsPreview($network);
        } else {
            $symbols = $this->symbolRepository->getSymbolsForTraining($network, $targetDate, $symbolOverwrite);
        }

        $this->logDebug('symbols for network found: ' . count($symbols));

        if (empty($symbols)) {
            throw new \Exception('no symbols specified');
        }

        foreach ($symbols as $symbol) {
            if (0 == count($symbol->getTrades())) {
                throw new \Exception('symbol has no trades attached');
            }
        }

        return $symbols;
    }

    public function getPredictionData(Network $network, Symbol $symbolOverwrite = null)
    {
        $networkData = new NetworkData();
        $this->logInfo(sprintf(
            'fetching prediction data for network %s (id: %s)',
            $network->getName(),
            $network->getId()
        ));

        $targetDate = new \DateTime('-' . $network->getTimeScope());
        if ($network->isInterpolateInputs()) {
            $targetDate = new \DateTime('-' .
                ($network->getInputSteps() + 1) * $network->getInterpolationInterval() . ' seconds'
            );
        }

        $symbols = $this->prepareSymbols($network, false, $targetDate, $symbolOverwrite);
        $this->prepareSourceInputs($symbols, $networkData);
        $this->prepareTrainingInputData($network, $networkData);

        return $networkData;
    }

    /**
     * @param Network $network
     * @param bool $preview
     * @return NetworkData
     * @throws \Exception
     */
    public function getNetworkData(Network $network, $preview = false):NetworkData
    {
        $networkData = new NetworkData();
        $this->logInfo('fetching network data for network "' . $network->getName().'"');
        if ($preview && $network->getInputSteps() > 3) {
            $network->setInputSteps(3);
        }
        $symbols = $this->prepareSymbols($network, $preview);

        $this->prepareSourceInputs($symbols, $networkData);

        $this->prepareTrainingInputData($network, $networkData);
        $this->prepareTrainingOutputData($network, $networkData);

        if ($network->isShuffleTrainingData()) {
            $this->shuffleNetworkData($networkData);
        }

        if (
            in_array($network->getOutputConfig()->getType(), [OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY, OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY]) &&
            $network->getBalanceHitsAndFails()) {
            $this->balanceHits($networkData);
        }

        return $networkData;
    }

    /**
     * @param Symbol[] $symbols
     * @param NetworkData $networkData
     */
    private function prepareSourceInputs(array $symbols, NetworkData $networkData)
    {
        foreach ($symbols as $symbol) {
            $sourceInput = new SourceInput();

            foreach ($symbol->getTrades() as $trade) {
                $tradeData = new TradeData();
                $tradeData->setPrice($trade->getPrice());
                $tradeData->setTime($trade->getTime());

                $this->updatePriceBoundaries($trade, $sourceInput);

                $sourceInput->addTradeData($tradeData);
            }

            $sourceInput->setSymbolName($symbol->getName());
            $sourceInput->setSymbolId($symbol->getId());
            $networkData->addSourceInput($sourceInput);
            $this->logInfo(sprintf(
                'source input "%s", max: %s, min: %s',
                    $sourceInput->getSymbolName(),
                    $sourceInput->getMaxPrice(),
                    $sourceInput->getMinPrice()
            ));
        }
    }

    /**
     * @param Symbol[] | array $symbols
     * @param \DateTime $dateOffset
     * @return array
     */
    private function prepareOrderBooks($symbols, \DateTime $dateOffset)
    {
        $orderBooks = [];
        foreach($symbols as $symbol) {
            $orderBooks[$symbol->getId()] = $this->orderBookRepository->findOrderBooks(
                $symbol,
                $dateOffset
            );
        }

        return $orderBooks;
    }

    /**
     * @param Network $network
     * @param NetworkData $networkData
     * @throws \Exception
     */
    private function prepareTrainingInputData(Network $network, NetworkData $networkData)
    {
        $trainingData = $networkData->getTrainingData();
        $tradesCount = $networkData->getSourceInputs()[0]->getTradesData()->count();

        $inputSteps = $network->getInputSteps();
        $limit = $this->getLimit($network, $tradesCount);

        $this->logInfo(sprintf(
            'preparing-training-data: trades count: %s, limit: %s, input steps: %s',
            $tradesCount,
            $limit,
            $inputSteps
        ));

        $orderBooks = [];

        if ($network->useOrderBooks()) {
            $orderBooks = $this->prepareOrderBooks($network->getSymbols(), new \DateTime('-' . $network->getTimeScope()));
        }

        $lastValue = [];

        for ($x = ($inputSteps - 1); $x < $limit; $x++) {
            $inputSets = [];
            for ($symbolOffset = 0; $symbolOffset < $networkData->getSourceInputs()->count(); $symbolOffset++) {

                $symbolData = $this->addTradesData($network, $networkData, $symbolOffset, $x, $lastValue);

                if ($network->useOrderBooks()) {
                    $symbolData = array_merge($symbolData, $this->addOrderBookData($network, $networkData, $symbolOffset, $x, $orderBooks));
                }

                if($network->isSeparateInputSymbols()) {
                    $inputSets[] = $symbolData;
                } else {
                    if (!isset($inputSets[0])) {
                        $inputSets[0] = [];
                    }
                    $inputSets[0] = array_merge($inputSets[0], $symbolData);
                }
            }
            foreach($inputSets as $row) {
                $trainingData->addInputSet($row);
            }
        }
    }

    /**
     * @param Network $network
     * @param NetworkData $networkData
     * @throws \Exception
     */
    private function prepareTrainingOutputData(Network $network, NetworkData $networkData)
    {
        $outputConfig = $network->getOutputConfig();
        if (empty($outputConfig)) {
            throw new \Exception('no output configured');
        }
        $outputConfig->setNetwork($network);
        switch ($outputConfig->getType()) {
            case OutputConfig::OUTPUT_TYPE_PREDICT_ONE_PRICE:
                $outputType = new PredictOnePriceOutputType();
                break;
            case OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY:
                $outputType = new HasRaisedByOutputType();
                break;
            case OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY:
                $outputType = new HasDroppedByOutputType();
                break;
            default:
                throw new \Exception('unsupported config type: ' . $outputConfig->getType());
        }

        $outputData = $outputType->getOutputData($outputConfig, $networkData);

        $networkData->getTrainingData()->setOutputs($outputData);
    }

    /**
     * @param $trade
     * @param $sourceInput
     */
    private function updatePriceBoundaries(Trade $trade, SourceInput $sourceInput)
    {
        if (!empty($trade->getUnnormalizedPrice())) {
            $price = $trade->getUnnormalizedPrice();
        } else {
            $price = $trade->getPrice();
        }
        if ($sourceInput->getMaxPrice() < $price) {
            $sourceInput->setMaxPrice($price);
        }
        if ($sourceInput->getMinPrice() == 0.0 || $sourceInput->getMinPrice() > $price) {
            $sourceInput->setMinPrice($price);
        }
    }

    /**
     * @param $networkData
     */
    private function shuffleNetworkData(NetworkData $networkData)
    {
        $trainingData = $networkData->getTrainingData();
        $outputs = $trainingData->getOutputs();
        $inputs = $trainingData->getInputs();
        $maxLen = min(count($outputs), count($inputs));

        $indices = array_keys(array_slice($outputs, 0, $maxLen));
        shuffle($indices);
        $newOutputs = [];
        $newInputs = [];
        foreach ($indices as $key => $index) {
            $newOutputs[] = $outputs[$index];
            $newInputs[] = $inputs[$index];
        }

        $trainingData->setOutputs($outputs);
        $trainingData->setInputs($inputs);
    }

    /**
     * @param NetworkData $networkData
     */
    private function balanceHits(NetworkData $networkData)
    {
        $trainingData = $networkData->getTrainingData();
        $outputs = $trainingData->getOutputs();
        $inputs = $trainingData->getInputs();
        $maxLen = min(count($outputs), count($inputs));
        $positiveHits = [];
        $positiveIndices = [];
        for ($x = 0; $x < $maxLen; $x++) {
            if (1 == $outputs[$x][1]) {
                $positiveHits[$x] = $inputs[$x];
                $positiveIndices[] = $x;
            }
        }

        if(empty($positiveIndices)) {
            $this->logInfo('no positive hits found, truncating training set to 0');
            $trainingData->setInputs([]);
            $trainingData->setOutputs([]);

            return;
        }

        $iterations = ($maxLen / 2) - count($positiveIndices);
        $this->logInfo(sprintf(
            'max len: %s, positive hits: %s, iterations to balance: %s',
            $maxLen,
            count($positiveIndices),
            $iterations
        ));

        for ($x = 0; $x < $iterations; $x++) {
            $setToOverwrite = array_rand(array_diff(array_keys($inputs), $positiveIndices), 1);
            $inputs[$setToOverwrite] = $positiveHits[array_rand($positiveHits, 1)];
            $outputs[$setToOverwrite] = [0, 1];
            $positiveIndices[] = $setToOverwrite;
        }

        $trainingData->setInputs($inputs);
        $trainingData->setOutputs($outputs);
    }

    /**
     * @param Network $network
     * @param $tradesCount
     * @return int
     */
    private function getLimit(Network $network, $tradesCount): int
    {
        if (Network::VALUE_TYPE_ABSOLUTE == $network->getValueType()) {
            $limit = $tradesCount;
        } else {
            $limit = $tradesCount - 1; //we need 2 trades for the percentage diff, and one-ahead for output
        }
        return $limit;
    }

    /**
     * @param Network $network
     * @param NetworkData $networkData
     * @param $symbolOffset
     * @param $x
     * @param $lastSymbolValues
     * @return array
     * @throws \Exception
     */
    private function addTradesData(Network $network, NetworkData $networkData, $symbolOffset, $x, &$lastSymbolValues): array
    {
        $symbolData = [];
        for ($n = 0; $n < $network->getInputSteps(); $n++) {
            switch ($network->getValueType()) {
                case Network::VALUE_TYPE_ABSOLUTE:
                    $symbolData[] = $networkData->getSourceInputs()[$symbolOffset]->getTradesData()[$x - $n]->getPrice();
                    break;
                case Network::VALUE_TYPE_PERCENTAGE:
                case Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED:
                    $currentPrice = $networkData->getSourceInputs()[$symbolOffset]->getTradesData()[$x - $n + 1]->getPrice();
                    $previousPrice = $networkData->getSourceInputs()[$symbolOffset]->getTradesData()[$x - $n]->getPrice();
                    if ($previousPrice == 0) {
                        throw new \Exception('previous trade has a price of "0", something is broken');
                    }
                    $change = ((100 / $previousPrice) * $currentPrice) - 100;
                    if (Network::VALUE_TYPE_PERCENTAGE == $network->getValueType()) {
                        $symbolData[] = ((100 / $previousPrice) * $currentPrice) - 100;
                    } else {
                        $symbolData[] = ($x > 0 ? $lastSymbolValues[$symbolOffset] : 0) + $change;
                        $lastSymbolValues[$symbolOffset] = $symbolData[count($symbolData) - 1];
                    }

                    break;
            }
        }

        return $symbolData;
    }

    /**
     * @param Network $network
     * @param NetworkData $networkData
     * @param $symbolOffset
     * @param $x
     * @param $orderBooks
     * @return array
     */
    private function addOrderBookData(Network $network, NetworkData $networkData, $symbolOffset, $x, $orderBooks): array
    {
        $symbolData = [];
        for ($n = 0; $n < $network->getInputSteps(); $n++) {
            $targetDate = $networkData->getSourceInputs()[$symbolOffset]->getTradesData()[$x - $n]->getTime();
            /** @var OrderBook $orderBook */
            foreach ($orderBooks[$networkData->getSourceInputs()[$symbolOffset]->getSymbolId()] as $orderBook) {
                if ($orderBook->getDate() >= $targetDate) {
                    $currentOrderBook = $orderBook;
                    break;
                }
            }

            for ($z = 0; $z < $network->getOrderBookSteps(); $z++) {
                $symbolData[] = empty($currentOrderBook) ? (float)0 : (float)$currentOrderBook->getBuy()[$z][0];
                $symbolData[] = empty($currentOrderBook) ? (float)0 : (float)$currentOrderBook->getBuy()[$z][1];
            }
            for ($z = 0; $z < $network->getOrderBookSteps(); $z++) {
                $symbolData[] = empty($currentOrderBook) ? (float)0 : (float)$currentOrderBook->getSell()[$z][0];
                $symbolData[] = empty($currentOrderBook) ? (float)0 : (float)$currentOrderBook->getSell()[$z][1];
            }
        }
        return $symbolData;
    }
}
