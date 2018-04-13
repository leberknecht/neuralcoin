<?php

namespace Tests\Functional\Fixtures\Service;

use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

class FakeRequestPredictionProducer implements ProducerInterface
{
    public function publish($msgBody, $routingKey = '', $additionalProperties = array())
    {

    }

    public function getChannel()
    {
        return new AmqpChannelMock();
    }
}
