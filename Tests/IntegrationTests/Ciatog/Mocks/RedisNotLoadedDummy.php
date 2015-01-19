<?php
namespace IntegrationTests\Ciatog\Mocks;

use Ciatog\RedisCache;
use Exception;

class RedisNotLoadedDummy extends RedisCache
{
    protected function getClient()
    {
        return new RedisNotLoadedClientDummy();
    }
}
