<?php

namespace PhpCache;

use Predis\Client;

class Redis extends AbstractCache {

    /**
     * Default cache validity time [s]
     *
     * @var int
     */
    private $timeThreshold = 7200;

    /**
     * Check if cache entry exist
     * @param CacheKey $key
     * @return boolean
     */
    public function check(CacheKey $key) {
        return (bool) $this->redis->exists($this->getKey($key));
    }

    /**
     * @var Client
     */
    private $redis;

    static public ?string $host = null;
    static public ?int $port = null;
    static public ?int $db = null;

    public function __construct() {

        $host = self::$host ?? (getenv('REDIS_HOST') ?: '127.0.0.1');
        $port = self::$port ?? (getenv('REDIS_PORT') ? (int) getenv('REDIS_PORT') : 6379);
        $db = self::$db ?? (getenv('REDIS_DB') ? (int) getenv('REDIS_DB') : 0);

        $this->redis = new Client(array(
            'host' => $host,
            'port' => $port
        ));

        $this->redis->select($db);
    }

    /**
     * Get cache value
     * @param CacheKey $key
     * @return mixed
     */
    public function get(CacheKey $key) {

        $value = $this->redis->get($this->getKey($key));

        if ($value === null) {
            return false;
        }

        $unserialized = @unserialize($value);

        if ($unserialized !== false) {
            $value = $unserialized;
        }

        return $value;
    }

    /**
     * Unset cache value
     * @param CacheKey $key
     */
    public function clear(CacheKey $key) {
        $this->redis->del($this->getKey($key));
    }

    /**
     * Unset all cache entries belonging to a module
     * @param CacheKey $key
     */
    public function clearModule(CacheKey $key) {

        $pattern = self::escapePattern(static::$sCachePrefix) . ':' . self::escapePattern($key->getModule()) . ':*';
        $iterator = new \Predis\Collection\Iterator\Keyspace($this->redis, $pattern);

        $keys = array();
        foreach ($iterator as $moduleKey) {
            $keys[] = $moduleKey;
        }

        if (!empty($keys)) {
            $this->redis->del($keys);
        }
    }

    /**
     * Escape Redis glob metacharacters (\ * ? [ ]) so the string is matched literally.
     * Module names often contain backslashes (namespaced class names), which Redis
     * would otherwise treat as escapes. All characters are escaped in a single pass
     * so the added backslashes are not escaped again.
     *
     * @param string $value
     * @return string
     */
    private static function escapePattern($value) {
        return preg_replace('/[\\\\*?\[\]]/', '\\\\$0', $value);
    }

    /**
     * Set cache value
     *
     * @param CacheKey $key
     * @param mixed $value
     * @param int $sessionLength
     */
    public function set(CacheKey $key, $value, $sessionLength = null) {

        if ($sessionLength == null) {
            $sessionLength = $this->timeThreshold;
        }

        $sKey = $this->getKey($key);

        if (is_array($value) || is_object($value)) {
            $value = serialize($value);
        }

        $this->redis->set($sKey, $value);

        if (!empty($sessionLength) && $sessionLength > 0) {
            $this->redis->expire($sKey, $sessionLength);
        }
    }

    /**
     * @param string $className
     * @depreciated
     */
    public function clearClassCache($className = null) {
        $this->redis->flushdb();
    }

    public function clearAll() {
        $this->redis->flushdb();
    }

}
