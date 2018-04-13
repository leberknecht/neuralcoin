<?php


namespace Tests\Functional\Fixtures\Service;

use PhpAmqpLib\Channel\AMQPChannel;

class AmqpChannelMock extends AMQPChannel
{
    public function __construct() {

    }

    public function queue_declare(
        $queue = '',
        $passive = false,
        $durable = false,
        $exclusive = false,
        $auto_delete = true,
        $nowait = false,
        $arguments = null,
        $ticket = null
    ) {
        return ['test', 23, 42];
    }

}