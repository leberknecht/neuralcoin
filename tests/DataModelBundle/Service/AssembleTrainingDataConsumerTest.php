<?php

namespace tests\DataModelBundle\Service;


use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\OutputConfig;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\NetworkRepository;
use DataModelBundle\Repository\TrainingRunRepository;
use DataModelBundle\Service\SerializerService;
use Doctrine\ORM\EntityManager;
use FeedBundle\Message\AssembleTrainingDataMessage;
use NetworkBundle\Service\AssembleTrainingDataConsumer;
use NetworkBundle\Service\NetworkTrainingService;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;

class AssembleTrainingDataConsumerTest extends \PHPUnit_Framework_TestCase
{
    /** @var NetworkTrainingService | PHPUnit_Framework_MockObject_MockObject  */
    private $networkTrainingServiceMock;
    /** @var NetworkRepository | PHPUnit_Framework_MockObject_MockObject  */
    private $networkRepoMock;
    /** @var TrainingRunRepository | PHPUnit_Framework_MockObject_MockObject  */
    private $trainingRunRepoMock;
    /** @var SerializerService | PHPUnit_Framework_MockObject_MockObject  */
    private $serializerServiceMock;
    /** @var Producer | PHPUnit_Framework_MockObject_MockObject producerMock */
    private $producerMock;
    /** @var  AssembleTrainingDataConsumer */
    private $consumer;
    /** @var  EntityManager | PHPUnit_Framework_MockObject_MockObject */
    private $emMock;

    public function setUp(){

        $this->networkTrainingServiceMock = $this->getMockBuilder(NetworkTrainingService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->networkRepoMock = $this->getMockBuilder(NetworkRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->trainingRunRepoMock = $this->getMockBuilder(TrainingRunRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->serializerServiceMock = $this->getMockBuilder(SerializerService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->producerMock = $this->getMockBuilder(Producer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumer = new AssembleTrainingDataConsumer(
            $this->networkRepoMock,
            $this->trainingRunRepoMock,
            $this->networkTrainingServiceMock,
            $this->serializerServiceMock,
            $this->producerMock
        );
        $this->consumer->setLogger(new NullLogger());
        $this->emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->consumer->setEntityManager($this->emMock);
    }

    public function testExecute()
    {
        $AMQPMessage = new AMQPMessage();
        $assembleTrainingDataMessage = new AssembleTrainingDataMessage();
        $assembleTrainingDataMessage->setNetworkId(42);
        $assembleTrainingDataMessage->setTrainingRunId(23);
        $AMQPMessage->setBody('{"networkId": 42, "trainingRunId":23}');
        $network = new Network();
        $network->setOutputConfig(new OutputConfig());
        $network->setFilePath('test.xml');
        $network->setInputLength(1);
        $network->setOutputLength(1);
        $trainingRun = new TrainingRun();
        $trainingRun->setTraningDataFile('test.csv');

        $this->serializerServiceMock->expects($this->once())
            ->method('deserialize')
            ->willReturn($assembleTrainingDataMessage);
        $this->networkRepoMock->expects($this->once())
            ->method('find')
            ->willReturn($network);
        $this->trainingRunRepoMock->expects($this->once())
            ->method('find')
            ->willReturn($trainingRun);
        $this->consumer->execute($AMQPMessage);
    }
}