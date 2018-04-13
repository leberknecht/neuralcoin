<?php

namespace tests\NetworkBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\NetworkData;
use DataModelBundle\Entity\TrainingData;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Service\SerializerService;
use Doctrine\ORM\EntityManager;
use League\Flysystem\Filesystem;
use NetworkBundle\Service\NetworkDataService;
use NetworkBundle\Service\NetworkTrainingService;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;

class NetworkTrainingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var NetworkTrainingService
     */
    private $networkTrainingService;
    /** @var NetworkDataService | PHPUnit_Framework_MockObject_MockObject */
    private $networkDataServiceMock;
    /** @var SerializerService | PHPUnit_Framework_MockObject_MockObject  */
    private $serializerServiceMock;
    /** @var Producer | PHPUnit_Framework_MockObject_MockObject */
    private $producerMock;
    /** @var Filesystem | PHPUnit_Framework_MockObject_MockObject  */
    private $filesystemMock;
    /** @var NetworkRepository | PHPUnit_Framework_MockObject_MockObject  */
    private $networkRepoMock;
    /** @var  EntityManager | PHPUnit_Framework_MockObject_MockObject */
    private $emMock;

    public function setUp()
    {
        $this->networkDataServiceMock = $this->getMockBuilder(NetworkDataService::class)
            ->disableOriginalConstructor()->getMock();
        $this->serializerServiceMock = $this->getMockBuilder(SerializerService::class)
            ->disableOriginalConstructor()->getMock();
        $this->producerMock = $this->getMockBuilder(Producer::class)
            ->disableOriginalConstructor()->getMock();
        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()->getMock();
        $this->networkRepoMock = $this->getMockBuilder(NetworkRepository::class)
            ->disableOriginalConstructor()->getMock();
        $this->networkTrainingService = new NetworkTrainingService(
            $this->networkDataServiceMock,
            $this->serializerServiceMock,
            $this->producerMock,
            $this->filesystemMock,
            $this->networkRepoMock
        );

        $this->emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->networkTrainingService->setEntityManager($this->emMock);
        $nullLogger = new NullLogger();
        $this->networkTrainingService->setLogger($nullLogger);
    }

    public function testTrainNetwork()
    {
        $network = new Network();
        $network->setId('test');
        $network->setFilePath('test.xml');
        $network->setInputLength(1);
        $network->setOutputLength(1);

        $this->producerMock->expects($this->once())
            ->method('publish');

        $trainingRun = $this->networkTrainingService->scheduleTraining($network);
        $this->assertInstanceOf(TrainingRun::class, $trainingRun);
    }

    public function testCreateTrainingData()
    {
        $network = new Network();
        $network->setId('test');
        $network->setFilePath('test.xml');
        $network->setInputLength(1);
        $network->setOutputLength(1);

        $networkData = new NetworkData();

        /** @var TrainingData | PHPUnit_Framework_MockObject_MockObject $trainingData */
        $trainingData = $this->getMockBuilder(TrainingData::class)->getMock();
        $trainingData->expects($this->once())
            ->method('getRawData')
            ->willReturn([ [ [0.5], [0.75] ], [ [0.75], [1.0] ] ]);
        $networkData->setTrainingData($trainingData);
        $this->networkDataServiceMock->expects($this->once())
            ->method('getNetworkData')
            ->willReturn($networkData);
        $trainingRun = (new TrainingRun())->setId('testrun');
        $actual = $this->networkTrainingService->createTrainingDataFile($network, $trainingRun);
        $this->assertEquals('test/training-run-testrun.csv', $trainingRun->getTraningDataFile());
    }

    public function testEmptyInAndOutputs()
    {
        $network = new Network();
        $networkData = new NetworkData();
        $trainingData = new TrainingData();
        $this->expectExceptionMessage('in- or outputs empty');
        $networkData->setTrainingData($trainingData);
        $this->networkDataServiceMock->expects($this->once())
            ->method('getNetworkData')
            ->willReturn($networkData);

        $this->networkTrainingService->createTrainingDataFile($network, new TrainingRun());
    }
}