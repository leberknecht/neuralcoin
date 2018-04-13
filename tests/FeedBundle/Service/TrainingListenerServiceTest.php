<?php

namespace Tests\FeedBundle\Service;

use DataModelBundle\Entity\Network;
use DataModelBundle\Entity\TrainingRun;
use DataModelBundle\Repository\TrainingRunRepository;
use Doctrine\ORM\EntityManager;
use FeedBundle\Service\TrainingListenerService;
use League\Flysystem\Filesystem;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;

class TrainingListenerServiceTest extends \PHPUnit_Framework_TestCase
{
    /** @var TrainingListenerService  */
    private $tradesListenerService;

    /** @var TrainingRunRepository | PHPUnit_Framework_MockObject_MockObject */
    private $trainingRunRepoMock;
    /** @var  EntityManager | PHPUnit_Framework_MockObject_MockObject */
    private $emMock;
    /** @var  Filesystem | PHPUnit_Framework_MockObject_MockObject */
    private $filesystemMock;
    /** @var  Filesystem | PHPUnit_Framework_MockObject_MockObject */
    private $imageFilesystemMock;

    public function setUp()
    {
        $this->trainingRunRepoMock = $this->getMockBuilder(TrainingRunRepository::class)->disableOriginalConstructor()->getMock();

        $this->emMock = $this->getMockBuilder(EntityManager::class)->disableOriginalConstructor()->getMock();
        $this->filesystemMock = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $this->imageFilesystemMock = $this->getMockBuilder(Filesystem::class)->disableOriginalConstructor()->getMock();
        $this->filesystemMock->expects($this->any())->method('read')->willReturn('<?xml version="1.0" encoding="UTF-8" standalone="no"?><svg width="40" height="40"><circle cx="20" cy="20" r="20" /></svg>');

        $this->tradesListenerService = new TrainingListenerService(
            $this->trainingRunRepoMock,
            $this->filesystemMock,
            $this->imageFilesystemMock
        );
        $this->tradesListenerService->setLogger(new NullLogger());
        $this->tradesListenerService->setEntityManager($this->emMock);

    }

    /**
     * @param $messageStatus
     * @param $entityStatus
     * @dataProvider getSatusUpdateTestData
     */
    public function testExecuteStatusInProgress($messageStatus, $entityStatus)
    {
        $AMQPMessage = new AMQPMessage();
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork(new Network());
        $this->trainingRunRepoMock->expects($this->once())->method('find')->willReturn($trainingRun);

        $AMQPMessage->setBody('{"trainingRunId": "42", "status": "'.$messageStatus.'", "error": "23","trainingSetLength": 420, "rawOutput": "Error: 23", "imagePath": "test-output.svg"}');
        $this->tradesListenerService->execute($AMQPMessage);
        $this->assertEquals($entityStatus, $trainingRun->getStatus());
    }

    public function getSatusUpdateTestData()
    {
        return [
          ['in progress', TrainingRun::STATUS_RUNNING],
          ['finished', TrainingRun::STATUS_FINISHED],
        ];
    }

    public function testExceptionOnUnknownStatus()
    {
        $this->expectException(\Exception::class);
        $AMQPMessage = new AMQPMessage();
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork(new Network());
        $this->trainingRunRepoMock->expects($this->once())->method('find')->willReturn($trainingRun);
        $AMQPMessage->setBody('{"trainingRunId": "42", "status": "unknown"}');
        $this->tradesListenerService->execute($AMQPMessage);
    }

    public function testPictureGeneration()
    {
        $AMQPMessage = new AMQPMessage();
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork(new Network());
        $this->trainingRunRepoMock->expects($this->once())->method('find')->willReturn($trainingRun);
        $AMQPMessage->setBody('{"trainingRunId": "42", "status": "finished", "error": "23","trainingSetLength": 420,"rawOutput": "Error: 23", "imagePath": "test-output.svg"}');
        $this->imageFilesystemMock->expects($this->once())->method('write');
        $this->tradesListenerService->execute($AMQPMessage);
    }

    public function testExceptionOccurred()
    {
        $AMQPMessage = new AMQPMessage();
        $trainingRun = new TrainingRun();
        $trainingRun->setNetwork(new Network());
        $this->trainingRunRepoMock->expects($this->once())->method('find')->willReturn($trainingRun);

        $AMQPMessage->setBody('{"trainingRunId": "42", "status": "error", "rawOutput": "Exception occurred"}');
        $this->tradesListenerService->execute($AMQPMessage);
        $this->assertEquals('Exception occurred', $trainingRun->getRawOutput());
        $this->assertEquals(TrainingRun::STATUS_ERROR, $trainingRun->getStatus());
    }
}
