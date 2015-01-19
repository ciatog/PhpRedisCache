<?php
namespace IntegrationTests\Ciatog;

use Ciatog\RedisCache;
use PHPUnit_Framework_TestCase;
use Predis\Client;

class RedisCacheTest extends PHPUnit_Framework_TestCase
{
    const TEST_CACHE_ITEM_KEY = "test_cache_item";
    const TEST_CACHE_ITEM_DATA = "test cache item data";
    const EXPIRED_TEST_CACHE_ITEM_KEY = "expired_test_cache_item";
    const EXPIRED_TEST_CACHE_ITEM_DATA = "expired test cache item data";

    private $redisClient;
    private $cachePrefix;
    private $uniqueContext;

    public function setUp()
    {
        $this->redisClient = new Client();
        $this->redisClient->flushAll();

        $this->uniqueContext = "TEST_CONTEXT";
        $this->cachePrefix = base64_encode($this->uniqueContext) . ":";

        $this->redisClient->set($this->formatKeyForCache(self::TEST_CACHE_ITEM_KEY), serialize(self::TEST_CACHE_ITEM_DATA));
        $this->redisClient->expire($this->formatKeyForCache(self::TEST_CACHE_ITEM_KEY), 3600);

        $this->redisClient->set($this->formatKeyForCache(self::EXPIRED_TEST_CACHE_ITEM_KEY), serialize(self::TEST_CACHE_ITEM_DATA));
        $this->redisClient->expire($this->formatKeyForCache(self::EXPIRED_TEST_CACHE_ITEM_KEY), 0);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage uniqueContext must be a string
     */
    public function __construct_whenNullSiteUrlPassed_throwsInvalidArgumentException()
    {
        new RedisCache(null);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Redis is currently not loaded
     */
    public function __construct_redisIsNotLoaded_throwsException()
    {
        new Mocks\RedisNotLoadedDummy($this->uniqueContext);
    }

    /**
     * @test
     */
    public function exists_cacheItemDoesNotExist_returnsFalse()
    {
        $cache = $this->getCache();

        $this->assertFalse($cache->exists("NO_SUCH_ITEM"));
    }

    /**
     * @test
     */
    public function exists_whenCacheItemDoesExist_returnsTrue()
    {
        $cache = $this->getCache();

        $this->assertTrue($cache->exists(self::TEST_CACHE_ITEM_KEY));
    }

    /**
     * @test
     */
    public function exists_whenExpiredCacheItemExists_returnsFalse()
    {
        $cache = $this->getCache();

        $this->redisClient->set($this->formatKeyForCache("TEST_KEY"), "Test Value");
        $this->redisClient->expire($this->formatKeyForCache("TEST_KEY"), -1);

        $this->assertFalse($cache->exists("TEST_KEY"));
    }

    /**
     * @test
     */
    public function get_whenCacheItemDoesNotExist_setsCacheItem()
    {
        $key = "NO_SUCH_KEY";
        $data = "cache data";
        $cache = $this->getCache();

        $item = $cache->get(
            $key,
            function ($config) use ($data) {
                $config->item = $data;
            }
        );

        $itemData = $this->redisClient->get($this->formatKeyForCache($key));
        $this->assertSame($data, unserialize($itemData));
    }

    /**
     * @test
     */
    public function get_whenNonExpiredCacheItemExists_returnsCachedData()
    {
        $cache = $this->getCache();
        $data = self::TEST_CACHE_ITEM_DATA;

        $item = $cache->get(
            self::TEST_CACHE_ITEM_KEY,
            function ($config) use ($data) {
                $config->item = $data;
            }
        );

        $this->assertSame($data, $item);
    }

    /**
     * @test
     */
    public function get_whenExpiredCacheItemExists_setsDataAndUpdatesExpiration()
    {
        $key = self::EXPIRED_TEST_CACHE_ITEM_KEY;
        $updatedData = "Updated data";
        $cache = $this->getCache();

        $data = $cache->get(
            $key,
            function ($config) use ($updatedData) {
                $config->item = $updatedData;
            }
        );

        $item = $this->redisClient->get($this->formatKeyForCache($key));
        $this->assertSame($data, unserialize($item));
    }

    /**
     * @test
     */
    public function set_whenNewCacheItem_savesNewCacheItemRecord()
    {
        $key = "new_test_item_key";
        $data = "new test item data";
        $cache = $this->getCache();

        $result = $cache->set($key, $data, 60);
        $this->assertTrue($result);

        $item = $this->redisClient->get($this->formatKeyForCache($key));
        $this->assertSame($data, unserialize($item));
    }

    /**
     * @test
     */
    public function set_whenExistingCacheItem_updatesExistingCacheProperties()
    {
        $key = self::TEST_CACHE_ITEM_KEY;
        $data = "new test item data";
        $cache = $this->getCache();

        $result = $cache->set($key, $data);
        $item = $this->redisClient->get($this->formatKeyForCache($key));

        $this->assertTrue($result);
        $this->assertSame($data, unserialize($item));
    }

    /**
     * @test
     */
    public function delete_whenCalledOnExistingItem_deletesItem()
    {
        $key = self::TEST_CACHE_ITEM_KEY;
        $cache = $this->getCache();

        $result = $cache->delete($key);
        $item = $this->redisClient->get($this->formatKeyForCache($key));

        $this->assertTrue($result);
        $this->assertNull($item);
    }

    /**
     * @test
     */
    public function deleteAll_whenCalledOnExistingItems_deletesAllItems()
    {
        $id = self::TEST_CACHE_ITEM_KEY;
        $cache = $this->getCache();

        $cache->set("TEST_2", 2);
        $cachedItemCount = count($this->redisClient->keys("*"));
        $this->assertSame(2, $cachedItemCount);

        $cache->deleteAll();
        $cachedItemCount = count($this->redisClient->keys("*"));
        $this->assertSame(0, $cachedItemCount);
    }

    /**
     * @test
     */
    public function exists_usesPrefix()
    {
        $key = "EXISTS_KEY";
        $cache = $this->getCache();

        $this->redisClient->set($key, "");

        $this->assertTrue($this->redisClient->exists($key));
        $this->assertFalse($cache->exists($key));
    }

    /**
     * @test
     */
    public function get_usesPrefix()
    {
        $key = "GET_KEY";
        $cache = $this->getCache();

        $this->redisClient->set($key, "");

        $this->assertTrue($this->redisClient->exists($key));
        $this->assertNull(
            $cache->get(
                $key,
                function () {
                }
            )
        );
    }

    /**
     * @test
     */
    public function set_usesPrefix()
    {
        $key = "SET_KEY";
        $cache = $this->getCache();

        $cache->set($key, "");

        $this->assertFalse($this->redisClient->exists($key));
        $this->assertTrue($this->redisClient->exists($this->formatKeyForCache($key)));
    }

    /**
     * @test
     */
    public function delete_usesPrefix()
    {
        $cache = $this->getCache();

        $cache->delete(self::TEST_CACHE_ITEM_KEY);

        $this->assertFalse($this->redisClient->exists($this->formatKeyForCache(self::TEST_CACHE_ITEM_KEY)));
    }

    /**
     * @test
     */
    public function keys_whenCalled_returnsListOfAllKeys()
    {
        $cache = $this->getCache();

        $cache->set("Test Key", "Test Data");

        $this->assertSame(
            [
                "Test Key",
                self::TEST_CACHE_ITEM_KEY
            ],
            $cache->keys()
        );
    }

    private function getCache()
    {
        return new RedisCache($this->uniqueContext);
    }

    private function formatKeyForCache($key)
    {
        return $this->cachePrefix . $key;
    }
}
