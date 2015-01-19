PHP Redis Cache
===============

A PHP Cache Wrapper around Redis

This library serves as a wrapper around Redis so it can be used as a cache. It has the following features:
 - The ability to set a unique context so you can use different caches in different circumstances. For instance, if you need to use the cache across multiple sites using the same Redis server you can pass in the site URL when creating the cache so each site has it's own cache context.
 - Automatically serialises your data when saving it in the cache.
 - Automatically deserialises your data when retrieving it from the cache.
 - Gives the ability to pass an anonymous configuration function that will be called if the item doesn't exist and allows you to generate the value needed.
