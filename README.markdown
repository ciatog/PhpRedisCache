[![Build Status](https://travis-ci.org/ciatog/PhpRedisCache.svg)](https://travis-ci.org/ciatog/PhpRedisCache)
[![Coverage Status](https://coveralls.io/repos/ciatog/PhpRedisCache/badge.svg?branch=master)](https://coveralls.io/r/ciatog/PhpRedisCache?branch=master)

PHP Redis Cache
===============

A PHP Cache Wrapper around Redis

This library serves as a wrapper around Redis so it can be used as a cache. It has the following features:
 - The ability to set a unique context so you can use different caches in different circumstances. For instance, if you need to use the cache across multiple sites using the same Redis server you can pass in the site URL when creating the cache so each site has it's own cache context.
 - Automatically serialises your data when saving it in the cache.
 - Automatically deserialises your data when retrieving it from the cache.
 - Gives the ability to pass an anonymous configuration function that will be called if the item doesn't exist and allows you to generate the value needed.

Usage
-----

Install the latest version with `composer require ciatog/redis-cache`

```php
<?php

use Ciatog\RedisCache;

// Creates an instance of the cache using `MY_CONTEXT` as the unique context
// for all operations
$cache = new RedisCache("MY_CONTEXT");

// Returns true/false depending on whether an item with this key exists in the cache.
$cache->exists("TEST_KEY");

// Return data in cache for key `TEST_KEY`.
// If that key is not in the cache then execute the function passed as the
// second argument, set the item in the cache to the data set on $config->item
// and finally return the item data.
$cache->get(
    "TEST_KEY",
    function ($config) {
        $config->item = "Test Data";
    }
);

// Deletes the item in the cache with this key if it exists
$cache->delete("TEST_KEY");

// Deletes all items in the cache
$cache->deleteAll();

// Returns a list of all the keys in the cache
$cache->keys();
```

 Author
 ------

 Keith Webster - <keith.webster@gmail.com> - <http://keith-webster.com>

 License
 -------

 PHP Redis Cache is licensed under the MIT License - see the `LICENSE.txt` file for details
