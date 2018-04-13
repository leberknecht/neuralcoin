<?php

namespace FeedBundle\Service;

use DataModelBundle\Service\BaseService;
use OldSound\RabbitMqBundle\RabbitMq\BaseAmqp;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;

class QueueInfoService extends BaseService
{
    private $knownConsumers = [];

    public function __construct(array $consumerMap)
    {
        foreach($consumerMap as $id => $consumer) {
            $this->knownConsumers[$id] = $consumer;
        }
    }

    public function getAllQueueInformation()
    {
        $queueInformation = [];
        /**
         * @var string $id
         * @var BaseAmqp $consumer
         */
        foreach($this->knownConsumers as $id => $consumer) {
            list(, $jobs, $consumers) = $consumer->getChannel()
                ->queue_declare($queue = $id,
                    $passive = true,
                    $durable = false
                );

            $queueInformation[$id] = [
                'jobs' => $jobs,
                'consumers' => $consumers,
            ];
        }

        return $queueInformation;
    }
}
