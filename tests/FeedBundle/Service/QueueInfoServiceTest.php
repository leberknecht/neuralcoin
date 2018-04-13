<?php

namespace Tests\FeedBundle\Service;

use FeedBundle\Service\QueueInfoService;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use PhpAmqpLib\Channel\AMQPChannel;
use PHPUnit_Framework_MockObject_MockObject;

class QueueInfoServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueueInfoService
     */
    private $queueInfoService;
    /** @var PHPUnit_Framework_MockObject_MockObject | BaseAmqp */
    private $queueInterfaceMock;

    public function setUp()
    {
        $this->queueInterfaceMock = $this->getMockBuilder(BaseAmqp::class)->disableOriginalConstructor()->getMock();
        $this->queueInfoService = new QueueInfoService([
            'test-queue-name' => $this->queueInterfaceMock,
            'test-queue-name2' => $this->queueInterfaceMock,
        ]);
    }

    public function testGetAllQueueInfo()
    {
        $channelMock = $this->getMockBuilder(AMQPChannel::class)->disableOriginalConstructor()->getMock();
        $this->queueInterfaceMock->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn($channelMock);
        $channelMock->expects($this->exactly(2))
            ->method('queue_declare')
            ->willReturn(['test', 23, 42]);
        $actual = $this->queueInfoService->getAllQueueInformation();
        $this->assertEquals([
            'test-queue-name' => [
                'consumers' => 42, 'jobs' => 23
            ],
            'test-queue-name2' => [
                'consumers' => 42, 'jobs' => 23
            ]
        ], $actual);
    }
}
