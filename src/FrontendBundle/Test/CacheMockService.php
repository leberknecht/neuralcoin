<?php

namespace FrontendBundle\Test;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class CacheMockService extends RedisAdapter
{
    protected function doFetch(array $ids)
    {
    }

    protected function doSave(array $values, $lifetime)
    {
    }
}
