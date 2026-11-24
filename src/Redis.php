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

    /**
     * Connect and read/write timeout [s]; null keeps the Predis defaults
     */
    static public ?float $timeout = null;

    /**
     * @var Client|null client returned by sGetClient()
     */
    static private ?Client $sClient = null;

    /**
     * @var string|null key prefix $sClient was created with
     */
    static private ?string $sClientPrefix = null;

    /**
     * @var Client|null client set with sSetClient(), returned as is
     */
    static private ?Client $sInjectedClient = null;

    public function __construct() {
        $this->redis = self::sCreateClient();
    }

    /**
     * Predis client for the commands the cache interface doesn't cover (SET NX PX, INCR, hashes, sorted
     * sets, EVAL, pipelines...). It uses the same connection settings as the cache, and every key is
     * prefixed with "<prefix>:" (the prefix set with sSetPrefix()), so its keys are separated the same way
     * as the cache entries. Pass keys without the prefix.
     *
     * Predis doesn't prefix SCAN patterns, and the keys SCAN and KEYS return carry the prefix: strip it
     * before passing them back to this client.
     *
     * @return Client
     */
    static public function sGetClient(): Client {

        if (self::$sInjectedClient !== null) {
            return self::$sInjectedClient;
        }

        $prefix = static::$sCachePrefix . ':';

        if (self::$sClient === null || self::$sClientPrefix !== $prefix) {
            self::$sClient = self::sCreateClient($prefix);
            self::$sClientPrefix = $prefix;
        }

        return self::$sClient;
    }

    /**
     * Replace the client returned by sGetClient() (e.g. with a fake in unit tests). Null goes back to
     * a real connection.
     *
     * @param Client|null $client
     */
    static public function sSetClient(?Client $client): void {
        self::$sInjectedClient = $client;
        self::$sClient = null;
        self::$sClientPrefix = null;
    }

    /**
     * @param string|null $prefix key prefix, null for none
     * @return Client
     */
    static private function sCreateClient(?string $prefix = null): Client {

        $parameters = array(
            'host' => self::$host ?? (getenv('REDIS_HOST') ?: '127.0.0.1'),
            'port' => self::$port ?? (getenv('REDIS_PORT') ? (int) getenv('REDIS_PORT') : 6379),
            'database' => self::$db ?? (getenv('REDIS_DB') ? (int) getenv('REDIS_DB') : 0)
        );

        if (self::$timeout !== null) {
            $parameters['timeout'] = self::$timeout;
            $parameters['read_write_timeout'] = self::$timeout;
        }

        return new Client($parameters, $prefix === null ? null : array('prefix' => $prefix));
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
