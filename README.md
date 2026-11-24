PhpCache
========

PhpCache is object PHP >=8 cache wrapper that offers similar way of handling various caching mechanisms:
* Memcached
* Redis
* Filesystem (deprecated)
* Session (deprecated)
* Variable (temporary, not available between requests)

#Example usage

```
require_once 'vendor/autoload.php';
$cache = \PhpCache\PhpCache::getInstance()->init('Redis')->getCache();

$key = new \PhpCache\CacheKey('myKey');
$cache->set($key, 'Lorem ipsum');
if ($cache->check($key)) {
    var_dump($cache->get($key));
} else {
    var_dump('Data not found');
}
```

#Methods available for all caching mechanisms:
* set - sets cache value based on provided key
* get - get data from cache based on provided key. Returns false when no data for key
* check - checks if data for provided key is set
* clear - removed data for key
* clearAll - flushes cache

#Development with Docker Compose

A `docker-compose.yml` is provided with a PHP 8 app container plus Redis and Memcached services.

```
docker compose up -d
docker compose exec app composer install
docker compose exec app vendor/bin/phpunit
```

The `Redis` and `Memcached` classes pick up `REDIS_HOST`/`REDIS_PORT`/`REDIS_DB` and `MEMCACHED_HOST`/`MEMCACHED_PORT` environment variables (already set in `docker-compose.yml`) so they connect to the `redis` and `memcached` services instead of `127.0.0.1`.

Host, port and (for Redis) db can also be passed directly to `init()`, which takes precedence over the environment variables:

```
$cache = \PhpCache\PhpCache::getInstance()->init('Redis', '10.0.0.5', 6380, 1)->getCache();

$cache = \PhpCache\PhpCache::getInstance()->init('Memcached', '10.0.0.5', 11212)->getCache();
```

## Raw Redis client

For commands the cache interface doesn't cover (`SET NX PX`, `INCR`, hashes, sorted sets, `EVAL`, pipelines), `Redis::sGetClient()` returns a Predis client with the same connection settings. Every key it touches gets the cache prefix, so pass keys without it:

```php
\PhpCache\AbstractCache::sSetPrefix('staging');
\PhpCache\Redis::sGetClient()->set('lock:worker', $id, 'NX', 'PX', 5000); // key "staging:lock:worker"
```

Predis doesn't prefix `SCAN` patterns, and keys returned by `SCAN` and `KEYS` include the prefix: strip it before passing them back to the client. `Redis::sSetClient($client)` replaces the client (e.g. with a fake in tests); `null` goes back to a real connection. `Redis::$timeout` sets the connect and read/write timeout of both the cache and this client.

#Changelog

## 3.1.0
* Added `Redis::sGetClient()`: a Predis client that prefixes every key with the cache prefix, for commands the cache interface doesn't cover. `Redis::sSetClient()` replaces it in tests.
* Added `Redis::$timeout` (connect and read/write timeout, seconds).
* The database number is passed as a Predis connection parameter, so the cache connects on first use instead of in the constructor.

## 3.0.1
* Fixed `Redis::clearModule()` silently deleting nothing (or deleting the wrong keys) when the module name or the prefix set with `sSetPrefix()` contains Redis glob characters (`\ * ? [ ]`). This affected every module created from a namespaced class, e.g. `new CacheKey($object)` or `new CacheKey('Gameplay\Items\JumpNode')`. Key format is unchanged, so 3.0.1 reads and writes the same keys as 3.0.0.
* Known limitation: keys are stored as `<prefix>:<module>:<property>`, so `clearModule()` on Redis also clears modules that start with the same name followed by a colon (clearing `Foo` also clears `Foo::bar`).
