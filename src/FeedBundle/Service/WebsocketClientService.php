<?php

namespace FeedBundle\Service;


use DataModelBundle\Service\BaseService;
use WebSocket\Client;

class WebsocketClientService extends BaseService
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function send($message)
    {
        $this->client->send($message);
    }

    public function __destruct()
    {
        $this->client->close();
    }
}
