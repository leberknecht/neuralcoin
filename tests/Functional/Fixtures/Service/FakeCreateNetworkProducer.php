<?php

namespace Tests\Functional\Fixtures\Service;

use OldSound\RabbitMqBundle\RabbitMq\RpcClient;

class FakeCreateNetworkProducer extends RpcClient
{
    public function addRequest($msgBody, $server, $requestId = null, $routingKey = '', $expiration = 0)
    {
        $this->replies[] = '{"status": "success"}';
    }
}
