<?php
namespace IntegrationTests\Ciatog\Mocks;

use Exception;

class RedisNotLoadedClientDummy
{
    public function ping()
    {
        throw new Exception();
    }
}
