<?php

namespace Tests\Functional\Fixtures\Service;

use WebSocket\Client;

class FakeWebsocketClient extends Client
{
    public function close($status = 1000, $message = 'ttfn')
    {

    }
}
