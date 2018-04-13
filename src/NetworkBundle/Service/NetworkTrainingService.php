<?php

namespace NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Repository\TrainingRunRepository;
use DataModelBundle\Service\BaseService;
use DataModelBundle\Service\SerializerService;
use FeedBundle\Message\AssembleTrainingDataMessage;
use FeedBundle\Message\CreateNetworkMessage;
use FeedBundle\Message\TrainNetworkMessage;
use League\Flysystem\FilesystemInterface;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class NetworkTrainingService extends BaseService
{
    const NETWORK_FILES_RELATIVE_PATH = '/./networks';

    /**
     * @var NetworkDataService
     */
    private $networkDataService;
    /**
     * @var SerializerService
     */
    private $serializer;
    /**
     * @var ProducerInterface
     */
    private $messageProducer;
    /**
     * @var FilesystemInterface
     */
    private $filesystem;
    /**
     * @var NetworkRepository
     */
    private $networkRepository;

    /**
     * NetworkService constructor
     * @param NetworkDataService  $networkDataService
     * @param SerializerService   $serializer
     * @param Producer            $messageProducer
     * @param FilesystemInterface $filesystem
     * @param NetworkRepository   $networkRepository
     */
    public function __construct(
        NetworkDataService $networkDataService,
        SerializerService $serializer,
        Producer $messageProducer,
        FilesystemInterface $filesystem,
        NetworkRepository $networkRepository
    ) {
        $this->networkDataService = $networkDataService;
        $this->serializer = $serializer;
        $this->messageProducer = $messageProducer;
        $this->filesystem = $filesystem;
        $this->networkRepository = $networkRepository;
    }

    public function scheduleTraining(Network $network)
    {
        $this->logInfo(sprintf(
            'scheduling network training for network "%s" (id: %s)',
            $network->getName(),
            $network->getId()
        ));
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork($network);
        $this->em->persist($trainingRun);
        $this->em->flush($trainingRun);

        $assembleTrainingDataMessage = new AssembleTrainingDataMessage();
        $assembleTrainingDataMessage->setNetworkId($network->getId());
        $assembleTrainingDataMessage->setTrainingRunId($trainingRun->getId());
        $this->messageProducer->setLogger($this->logger);
        $message = $this->serializer->serialize($assembleTrainingDataMessage);
        $this->messageProducer->publish($message);
        $this->logInfo('training run scheduled, training-data preparation requested, message: ' . $message);
        return $trainingRun;
    }

    /**
     * @param array $trainingSets
     * @return string
     */
    public function convertTrainingDataToCsv(array $trainingSets)
    {
        $result = '';
        foreach ($trainingSets as $trainingSet) {
            $result .= implode(',', array_merge($trainingSet[0], $trainingSet[1])).PHP_EOL;
        }
        return $result;
    }

    /**
     * @param Network $network
     * @param TrainingRun $trainingRun
     * @return string
     * @throws \Exception
     */
    public function createTrainingDataFile(Network $network, TrainingRun $trainingRun)
    {
        $trainingDataFilename = sprintf('%s/training-run-%s.csv',$network->getId(), $trainingRun->getId());
        $rawData = $this->networkDataService->getNetworkData($network)->getTrainingData()->getRawData();
        $csvData = $this->convertTrainingDataToCsv($rawData);
        $this->filesystem->put($trainingDataFilename, $csvData);
        $trainingRun->setTraningDataFile($trainingDataFilename);

        return $trainingDataFilename;
    }
}
