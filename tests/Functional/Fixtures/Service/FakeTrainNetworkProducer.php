<?php

namespace Tests\Functional\Fixtures\Service;

use OldSound\RabbitMqBundle\RabbitMq\Producer;

class FakeTrainNetworkProducer extends Producer
{
    public function publish($msgBody, $routingKey = '', $additionalProperties = array(), array $headers = null)
    {
        return true;
    }

    public function getChannel()
    {
        return new AmqpChannelMock();
    }
}
