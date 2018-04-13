<?php

namespace Tests\Functional\Fixtures\Service;

use OldSound\RabbitMqBundle\RabbitMq\RpcClient;

class FakeGetPredictionProducer extends RpcClient
{
    public function addRequest($msgBody, $server, $requestId = null, $routingKey = '', $expiration = 0)
    {
        $stdClass = new \stdClass();
        $stdClass->outputs = [23];
        $this->replies[] = $stdClass;
    }
}