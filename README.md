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