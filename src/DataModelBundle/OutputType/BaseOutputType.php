<?php


namespace DataModelBundle\OutputType;


use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\Trade;
use Doctrine\Common\Collections\ArrayCollection;

abstract class BaseOutputType
{
    public function getOutputData(OutputConfig $outputConfig, NetworkData $networkData):array
    {
        $outputData = [];
        $targetSourceInputs = $this->getOutputSymbols($outputConfig, $networkData);

        foreach ($targetSourceInputs as $inputSymbol) {
            /** @var Trade[]|ArrayCollection $sourceTrades */
            $sourceTrades = $inputSymbol->getTradesData();
            $offset = $outputConfig->getStepsAhead();
            if (in_array($outputConfig->getNetwork()->getValueType(), [
                    Network::VALUE_TYPE_PERCENTAGE, Network::VALUE_TYPE_PERCENTAGE_ACCUMULATED,
                ])) {
                $offset++; //as we need 2 values to calc the percentage-diff
            }
            $trades = new ArrayCollection(
                $sourceTrades->slice($offset)
            );
            $outputData[] = $this->calculateOutputData($outputConfig, $trades, $sourceTrades);
        }

        if (empty($outputData)) {
            throw new \Exception('calculated output data is empty');
        }
        $result = [];
        $setLength = count($outputData[0]);
        for ($x = 0; $x < $setLength; $x++) {
            foreach($outputData as $outputSet) {
                $result[] = $outputSet[$x];
            }
        }

        return $result;
    }

    /**
     * @param OutputConfig $outputConfig
     * @param $trades
     * @param $sourceTrades
     * @return array
     */
    abstract protected function calculateOutputData(OutputConfig $outputConfig, $trades, $sourceTrades): array;

    /**
     * @param Prediction $prediction
     * @param Trade $predictedTrade
     * @return
     */
    abstract public function evaluatePrediction(Prediction $prediction, Trade $predictedTrade);

    /**
     * @param NetworkData $networkData
     * @param Prediction $prediction
     * @param $max
     * @param $min
     * @return mixed
     */
    abstract public function setPredictedPrice(NetworkData $networkData, Prediction $prediction, $max, $min);

    /**
     * @param OutputConfig $outputConfig
     * @param NetworkData $networkData
     * @return array
     * @throws \Exception
     */
    private function getOutputSymbols(OutputConfig $outputConfig, NetworkData $networkData): array
    {
//the "target" input we want to use as output
        $targetSourceInputs = [];
        if ($outputConfig->getNetwork()->isSeparateInputSymbols()) {
            foreach ($networkData->getSourceInputs() as $sourceInput) {
                $targetSourceInputs[] = $sourceInput;
            }
        } else {
            $targetSymbol = $outputConfig->getPricePredictionSymbol();
            foreach ($networkData->getSourceInputs() as $sourceInput) {
                if ($sourceInput->getSymbolId() == $targetSymbol->getId()) {
                    $targetSourceInputs[] = $sourceInput;
                }
            }

            if (empty($targetSourceInputs)) {
                throw new \Exception('target input "'.$targetSymbol->getId().'" not found in source-inputs');
            }
        }

        return $targetSourceInputs;
    }
}