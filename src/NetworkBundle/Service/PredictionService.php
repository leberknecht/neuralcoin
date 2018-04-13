<?php

namespace NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\Prediction;
use DataModelBundle\Entity\SourceInput;
use DataModelBundle\Entity\Symbol;
use DataModelBundle\Entity\Trade;
use DataModelBundle\Entity\TradeData;
use DataModelBundle\OutputType\BaseOutputType;
use DataModelBundle\OutputType\HasDroppedByOutputType;
use DataModelBundle\OutputType\HasRaisedByOutputType;
use DataModelBundle\OutputType\PredictOnePriceOutputType;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Repository\PredictionRepository;
use DataModelBundle\Repository\TradeRepository;
use DataModelBundle\Service\BaseService;
use DataModelBundle\Service\SerializerService;
use Doctrine\Common\Collections\ArrayCollection;
use FeedBundle\Message\PredictionMessage;
use FeedBundle\Message\RequestPredictionMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use OldSound\RabbitMqBundle\RabbitMq\RpcClient;
use PhpAmqpLib\Message\AMQPMessage;

class PredictionService extends BaseService implements ConsumerInterface
{
    /**
     * @var Producer
     */
    private $rpcClient;
    /**
     * @var SerializerService
     */
    private $serializerService;
    /**
     * @var NetworkDataService
     */
    private $networkDataService;
    /**
     * @var TradeRepository
     */
    private $tradeRepository;
    /**
     * @var ProducerInterface
     */
    private $predictionProducer;
    /**
     * @var PredictionRepository
     */
    private $predictionRepository;

    /**
     * PredictionService constructor.
     * @param RpcClient $rpcClient
     * @param SerializerService $serializerService
     * @param NetworkDataService $networkDataService
     * @param TradeRepository $tradeRepository
     * @param ProducerInterface $predictionProducer
     * @param PredictionRepository $predictionRepository
     */
    public function __construct(
        RpcClient $rpcClient,
        SerializerService $serializerService,
        NetworkDataService $networkDataService,
        TradeRepository $tradeRepository,
        ProducerInterface $predictionProducer,
        PredictionRepository $predictionRepository
    )
    {
        $this->rpcClient = $rpcClient;
        $this->serializerService = $serializerService;
        $this->networkDataService = $networkDataService;
        $this->tradeRepository = $tradeRepository;
        $this->predictionProducer = $predictionProducer;
        $this->predictionRepository = $predictionRepository;
    }

    public function execute(AMQPMessage $msg)
    {
        $this->em->clear();
        $this->logInfo('get prediction request: ' . $msg->getBody());
        /** @var RequestPredictionMessage $message */
        $message = $this->serializerService->deserialize($msg->getBody(), RequestPredictionMessage::class);
        /** @var Prediction $prediction */
        $prediction =$this->predictionRepository->find($message->getPredictionId());
        if (empty($prediction)) {
            throw new \Exception('unknown prediction: ' . $message->getPredictionId());
        }
        $this->getPrediction($prediction);
        $this->logInfo('prediction processed');
    }

    public function requestPrediction(Network $network)
    {
        $prediction = new Prediction();
        $prediction->setNetwork($network);
        $this->em->persist($prediction);
        $this->em->flush();
        $requestPredictionMessage = new RequestPredictionMessage();
        $requestPredictionMessage->setPredictionId($prediction->getId());

        $message = $this->serializerService->serialize($requestPredictionMessage);
        $this->logInfo(sprintf(
            'requesting prediction for network: %s, prediction-id: %s, message: \'%s\'',
            $prediction->getNetwork()->getId(),
            $prediction->getId(),
            $message
        ));

        $this->predictionProducer->publish($message);
        return $prediction;
    }

    /**
     * @param Prediction $prediction
     * @param Symbol|null $symbolOverwrite
     * @return Prediction
     */
    public function getPrediction(Prediction $prediction, Symbol $symbolOverwrite = null)
    {
        $this->rpcClient->setLogger($this->logger);
        $this->logInfo('starting to fetch network data');
        $networkData = $this->networkDataService->getPredictionData($prediction->getNetwork(), $symbolOverwrite);
        $data = $networkData->getTrainingData()->getInputs();
        $inputData = array_pop($data);

        $this->logInfo(sprintf(
            'processing prediction: %s, network: %s (%s), input-data: %s',
            $prediction->getId(),
            $prediction->getNetwork()->getId(),
            $prediction->getNetwork()->getName(),
            var_export($inputData, true)
        ));
        $prediction = $this->updatePrediction($prediction, $inputData);
        $predictionMessage = $this->createPredictionMessage($prediction);

        $prediction->setOutputData($this->sendPredictionMessage($predictionMessage));
        $this->setPredictedPrice($networkData, $prediction);
        $this->logInfo(sprintf(
            'prediction created, outputs: %s, eval to predicted price: ',
            var_export($prediction->getOutputData(), true),
            $prediction->getPredictedValue()
        ));

        $prediction->getNetwork()->addPrediction($prediction);
        $this->em->flush($prediction);
        $this->em->flush($prediction->getNetwork());

        return $prediction;
    }

    /**
     * @param Prediction $prediction
     * @param $inputData
     * @return Prediction
     * @throws \Exception
     */
    private function updatePrediction(Prediction $prediction, $inputData): Prediction
    {
        $prediction->setInputData($inputData);
        $network = $prediction->getNetwork();
        switch($network->getOutputConfig()->getType()) {
            case OutputConfig::OUTPUT_TYPE_HAS_DROPPED_BY:
            case OutputConfig::OUTPUT_TYPE_HAS_RAISED_BY:
            case OutputConfig::OUTPUT_TYPE_PREDICT_ONE_PRICE:
                $currentPrice = $this->tradeRepository->findLastPrice(
                    $network->getOutputConfig()->getPricePredictionSymbol(),
                    true,
                    $network->getExchange()
                );
                $prediction->setPriceAtPrediction($currentPrice);
                break;
            default:
                throw new \Exception(
                    'unsupported output type for prediction: ' . $network->getOutputConfig()->getType()
                );
        }
        $this->em->persist($prediction);
        $this->em->flush($prediction);

        return $prediction;
    }

    /**
     * @param Prediction $prediction
     * @return PredictionMessage
     */
    private function createPredictionMessage(Prediction $prediction): PredictionMessage
    {
        $predictionMessage = new PredictionMessage();
        $predictionMessage->setFromNetwork($prediction->getNetwork());
        $predictionMessage->setPredictionId($prediction->getId());
        $predictionMessage->setInputData($prediction->getInputData());

        return $predictionMessage;
    }

    /**
     * @param $predictionMessage
     * @return array
     * @throws \Exception
     */
    private function sendPredictionMessage(PredictionMessage $predictionMessage)
    {
        $this->rpcClient->setLogger($this->logger);
        $requestId = uniqid('prediction.');
        $this->rpcClient->addRequest(
            $this->serializerService->serialize($predictionMessage),
            'get-prediction',
            $requestId
        );

        $replies = $this->rpcClient->getReplies();
        if (empty($replies) || !($reply = array_pop($replies))) {
            throw new \Exception('no reply from get-prediction RPC service');
        }

        return $reply->outputs;
    }

    /**
     * @param Prediction $prediction
     * @throws \Exception
     */
    public function checkPrediction(Prediction $prediction)
    {
        $outputConfig = $prediction->getNetwork()->getOutputConfig();
        $outputType = null;
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
                throw new \Exception('unsupported output type: ' . $outputConfig->getType());
        }
        $predictedTrade = $this->tradeRepository->findPredictedTrade($prediction);
        if (!empty($predictedTrade)) {
            $this->logInfo(sprintf(
                'predicted trade found! prediction time: %s trade time: %s  net steps-ahead: %s, interpolate: %s',
                $prediction->getCreatedAt()->format('Y-m-d H:i:s'),
                $predictedTrade->getTime()->format('Y-m-d H:i:s'),
                $prediction->getNetwork()->getOutputConfig()->getStepsAhead(),
                $prediction->getNetwork()->isInterpolateInputs() ? 'yes' : 'no'
            ));
            $prediction->setFinished(true);
            $outputType->evaluatePrediction($prediction, $predictedTrade);
            $prediction->getNetwork()->setDirectionHitRatio(
                $this->predictionRepository->findDirectionHitRatio($prediction->getNetwork())
            );
            $this->logInfo('network success ratio: ' . $prediction->getNetwork()->getDirectionHitRatio());
            $this->em->flush();
        }
    }

    /**
     * @param Network $network
     * @param $networkData
     * @return array
     */
    private function getSymbolPriceBoundaries(Network $network, NetworkData $networkData): array
    {
        $max = 0;
        $min = 0;
        foreach ($networkData->getSourceInputs() as $sourceInput) {
            if ($sourceInput->getSymbolId() == $network->getOutputConfig()->getPricePredictionSymbol()->getId()) {
                /** @var TradeData $tradeData */
                $max = $sourceInput->getMaxPrice();
                $min = $sourceInput->getMinPrice();
            }
        }

        return array($max, $min);
    }

    /**
     * @param NetworkData $networkData
     * @param Prediction $prediction
     * @throws \Exception
     */
    private function setPredictedPrice(NetworkData $networkData, Prediction $prediction)
    {
        $network = $prediction->getNetwork();
        list($max, $min) = $this->getSymbolPriceBoundaries($network, $networkData);
        $outputType = null;
        switch ($network->getOutputConfig()->getType()) {
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
                throw new \Exception('unsupported output type: ' . $network->getOutputConfig()->getType());
        }
        $outputType->setPredictedPrice($networkData, $prediction, $max, $min);
    }
}