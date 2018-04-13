<?php

namespace Tests\FeedBundle\Service;

use FeedBundle\Service\FrontendSessionService;
use FeedBundle\Service\QuoteManagerService;
use FeedBundle\Service\TradesListenerService;
use FeedBundle\Service\WebsocketClientService;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit_Framework_MockObject_MockBuilder;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Log\NullLogger;
use Ratchet\Server\IoConnection;
use Ratchet\WebSocket\Version\RFC6455\Connection;

class TradesListenerServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsocketClientService | PHPUnit_Framework_MockObject_MockObject
     */
    private $websocketServiceMock;
    /**
     * @var TradesListenerService
     */
    private $tradesListenerService;

    /** @var  NullLogger | PHPUnit_Framework_MockObject_MockObject */
    private $loggerMock;
    /** @var  QuoteManagerService | PHPUnit_Framework_MockObject_MockObject */
    private $quoteManagerMock;

    public function setUp()
    {
        $this->websocketServiceMock = $this->getMockBuilder(WebsocketClientService::class)->disableOriginalConstructor()->getMock();
        $this->quoteManagerMock = $this->getMockBuilder(QuoteManagerService::class)->disableOriginalConstructor()->getMock();
        $this->tradesListenerService = new TradesListenerService(
            $this->websocketServiceMock,
            $this->quoteManagerMock,
            'testing'
        );
        $this->loggerMock = $this->getMockBuilder(NullLogger::class)->disableOriginalConstructor()->getMock();
        $this->tradesListenerService->setLogger($this->loggerMock);
    }

    public function testProcessMessage()
    {
        $AMQPMessage = new AMQPMessage();
        $AMQPMessage->setBody('{"test": 42}');
        $this->websocketServiceMock->expects($this->once())->method('send')->with(
            '{"test":42,"push_secret":"testing"}'
        );
        $this->tradesListenerService->execute($AMQPMessage);
    }
}
