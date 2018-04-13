<?php

namespace NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Exception\InsufficientTrainingDataException;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Repository\TrainingRunRepository;
use DataModelBundle\Service\BaseService;
use DataModelBundle\Service\SerializerService;
use FeedBundle\Message\AssembleTrainingDataMessage;
use FeedBundle\Message\TrainNetworkMessage;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;

class AssembleTrainingDataConsumer extends BaseService implements ConsumerInterface
{
    /**
     * @var NetworkRepository
     */
    private $networkRepository;
    /**
     * @var TrainingRunRepository
     */
    private $trainingRunRepository;
    /**
     * @var NetworkTrainingService
     */
    private $networkTrainingService;
    /**
     * @var SerializerService
     */
    private $serializerService;
    /**
     * @var Producer
     */
    private $trainingProducer;

    public function __construct(
        NetworkRepository $networkRepository,
        TrainingRunRepository $trainingRunRepository,
        NetworkTrainingService $networkTrainingService,
        SerializerService $serializerService,
        Producer $trainingProducer
    )
    {
        $this->networkRepository = $networkRepository;
        $this->trainingRunRepository = $trainingRunRepository;
        $this->networkTrainingService = $networkTrainingService;
        $this->serializerService = $serializerService;
        $this->trainingProducer = $trainingProducer;
    }

    public function execute(AMQPMessage $msg)
    {
        $this->em->clear();
        $this->trainingProducer->setLogger($this->logger);
        $this->logInfo('processing training-data-request: ' . $msg->getBody());
        /** @var AssembleTrainingDataMessage $message */
        $message = $this->serializerService->deserialize($msg->getBody(), AssembleTrainingDataMessage::class);
        /** @var Network $network */
        $this->logDebug('network id: ' . $message->getNetworkId());
        $network = $this->networkRepository->find($message->getNetworkId());
        /** @var TrainingRun $trainingRun */
        $trainingRun = $this->trainingRunRepository->find($message->getTrainingRunId());

        $this->logInfo(sprintf(
            'preparing training data for network "%s" (id: %s), training run: %s',
            $network->getName(),
            $network->getId(),
            $trainingRun->getId()
        ));
        $trainingRun->setStartedAt(new \DateTime());
        $trainingRun->setStatus(TrainingRun::STATUS_PREPARE_TRAINING_DATA);
        $this->em->flush($trainingRun);

        try
        {
            $this->networkTrainingService->createTrainingDataFile($network, $trainingRun);
            $trainingRun->setTrainingDataPreparedAt(new \DateTime());
            $trainingRun->setStatus(TrainingRun::STATUS_PREPARED);
            $this->em->flush($trainingRun);
            $trainNetworkMessage = $this->createTrainingMessage($network, $trainingRun);

            $this->logInfo(sprintf(
                'network file: %s , training-data: %s',
                $network->getFilePath(),
                $trainingRun->getTraningDataFile()
            ));

            $this->trainingProducer->publish($this->serializerService->serialize($trainNetworkMessage));
        } catch (InsufficientTrainingDataException $exception) {
            $this->logInfo('not enough data to train, training run: ' . $trainingRun->getId());
            $trainingRun->setStatus(TrainingRun::STATUS_SKIPPED);
        } catch (\Exception $exception) {
            $this->logError('error occured, error-message: ' . $exception->getMessage());
            $trainingRun->setStatus(TrainingRun::STATUS_ERROR);
            $trainingRun->setRawOutput($exception->getMessage());
        }
        $this->em->flush($trainingRun);
    }

    /**
     * @param Network $network
     * @param TrainingRun $trainingRun
     * @return TrainNetworkMessage
     */
    private function createTrainingMessage(Network $network, TrainingRun $trainingRun): TrainNetworkMessage
    {
        $trainNetworkMessage = new TrainNetworkMessage();
        $trainNetworkMessage->setFromNetwork($network);
        $trainNetworkMessage->setTrainingDataFile($trainingRun->getTraningDataFile());
        $trainNetworkMessage->setTrainingRunId($trainingRun->getId());
        $trainNetworkMessage->setEpochs($network->getEpochsPerTrainingRun());
        $trainNetworkMessage->setGenerateImage($network->isGenerateImage());

        return $trainNetworkMessage;
    }
}