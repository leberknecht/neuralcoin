<?php

namespace FeedBundle\Service;

use DataModelBundle\Service\BaseService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;

class TradesListenerService extends BaseService implements ConsumerInterface
{
    /**
     * @var WebsocketClientService
     */
    private $clientService;
    /**
     * @var QuoteManagerService
     */
    private $quoteManagerService;
    /**
     * @var
     */
    private $pushSecret;

    public function __construct(
        WebsocketClientService $clientService,
        QuoteManagerService $quoteManagerService,
        $pushSecret
    )
    {
        $this->clientService = $clientService;
        $this->quoteManagerService = $quoteManagerService;
        $this->pushSecret = $pushSecret;
    }

    public function execute(AMQPMessage $msg)
    {
        $this->quoteManagerService->saveTradeFromMessage($msg->getBody());
        $broadcastMessage = json_decode($msg->getBody(), true);
        $broadcastMessage['push_secret'] = $this->pushSecret;
        $this->clientService->send(json_encode($broadcastMessage));
    }
}
