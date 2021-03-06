<?php
namespace Ciatog;

use Exception;
use InvalidArgumentException;
use Predis\Client;
use StdClass;

class RedisCache
{
	const NO_EXPIRATION = null;

	private $client;
	private $uniqueContext;

	/**
	 * Initialises the class with a unique context so you can share a redis cache amongst multiple different applications.
	 */
	public function __construct($uniqueContext)
	{
		if (is_null($uniqueContext) || !is_string($uniqueContext)) {
			throw new InvalidArgumentException("uniqueContext must be a string");
		}

		$this->uniqueContext = $uniqueContext;
		$this->client = $this->getClient();

		$this->checkIfRedisIsLoaded();
	}

	private function checkIfRedisIsLoaded()
	{
		$redisClient = $this->getClient();
		try {
			$redisClient->ping();
			return true;
		} catch (Exception $e) {
			throw new Exception("Redis is currently not loaded");
		}
	}

	protected function getClient()
	{
		return new Client(
			null,
			[
				"prefix" => base64_encode($this->uniqueContext) . ":"
			]
		);
	}

	/**
	 * Checks if the item with the specified keys exists in the cache
	 */
	public function exists($key)
    {
        return $this->client->exists($key);
    }

	/**
	 * Retrieves an item from the cache with the specified key.
	 * If you pass a function as the second parameter this will be called if the item does not exist in the database.
	 * The function should return the data that you could like added to the cache.
	 */
    public function get($key, $configFunc = null)
    {
        $item = $this->client->get($key);
        if (is_null($item) && $configFunc) {
            $config = new \StdClass();
            $config->item = null;
            $config->expiresInSeconds = self::NO_EXPIRATION;

            $configFunc($config);

            $this->set($key, $config->item, $config->expiresInSeconds);

            return $config->item;
        } else {
            return unserialize($item);
        }
    }

	/**
	 * Adds an item to the cache with the specified key. Also allows an optional expiration (default is no expiration)
	 */
    public function set($key, $data, $expiresInSeconds = self::NO_EXPIRATION)
    {
    	$this->client->set($key, serialize($data));

        if ($expiresInSeconds !== self::NO_EXPIRATION) {
            $this->client->expire($key, $expiresInSeconds);
        }

    	return true;
    }

	/**
	 * Deletes the item with the specified key from the current cache context
	 */
    public function delete($key)
    {
    	$this->client->del($key);

        return true;
    }

	/**
	 * Deletes all items from the current cache context
	 */
    public function deleteAll()
    {
        return $this->client->flushdb();
    }

	/**
	 * Returns a list of keys from the current cache context
	 */
    public function keys()
    {
        $keys = $this->client->keys("*");
        $keysWithUniqueContext = array_map(
			function ($key) {
				return explode(":", $key)[1];
			},
			$keys
		);

        sort($keysWithUniqueContext);

        return $keysWithUniqueContext;
    }
}
