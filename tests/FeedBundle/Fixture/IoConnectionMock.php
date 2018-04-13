<?php


namespace Tests\FeedBundle\Fixture;

use Ratchet\Server\IoConnection;

class IoConnectionMock extends IoConnection
{
    public $resourceId = 42;
    public $remoteAddress = '127.0.0.1';
}