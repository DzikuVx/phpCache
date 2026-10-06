<?php

namespace PhpCache;

class Memcached extends AbstractCache {

	private $timeThreshold = 7200;

	private $memcached = null;

	static public $host = null;
	static public $port = null;

	public function __construct() {

		$host = self::$host ?? (getenv('MEMCACHED_HOST') ?: '127.0.0.1');
		$port = self::$port ?? (getenv('MEMCACHED_PORT') ? (int) getenv('MEMCACHED_PORT') : 11211);

		$this->memcached = new \Memcached();
		$this->memcached->addServer($host, $port);
	}

	public function check(CacheKey $key) {

		$tValue = $this->get($key);

		if ($tValue === false) {
			return false;
		}else {
			return true;
		}

	}

	public function get(CacheKey $key) {
		return $this->memcached->get($this->getKey($key));
	}

	/**
	 * Unset cache value
	 * @param CacheKey $key
	 */
	function clear(CacheKey $key) {
		$this->memcached->delete($this->getKey($key));
	}

	/**
	 * Unset all cache entries belonging to a module.
	 *
	 * Memcached has no way to enumerate or delete keys by pattern, so modules
	 * are namespaced by a version number kept in a dedicated key. Bumping it
	 * makes every previously cached entry for the module unreachable; the
	 * stale entries themselves fall out of memcached naturally once their
	 * own TTL expires.
	 *
	 * @param CacheKey $key
	 */
	public function clearModule(CacheKey $key) {

		$versionKey = $this->getModuleVersionKey($key->getModule());

		if ($this->memcached->increment($versionKey, 1) === false) {
			$this->memcached->add($versionKey, 2);
		}
	}

	public function set(CacheKey $key, $value, $sessionLength = null) {

		if ($sessionLength == null) {
			$sessionLength = $this->timeThreshold;
		}

		$this->memcached->set($this->getKey($key), $value, $sessionLength);
	}

    /**
     * @param null $className
     * @depreciated
     */
	public function clearClassCache(/** @noinspection PhpUnusedParameterInspection */
        $className = null) {
		$this->memcached->flush();
	}

	public function clearAll() {
		$this->memcached->flush();
	}

	/**
	 * @inheritDoc
	 */
	protected function getKey(CacheKey $key) {
		return parent::getKey($key) . ':v' . $this->getModuleVersion($key->getModule());
	}

	/**
	 * @param string $module
	 * @return int
	 */
	private function getModuleVersion($module) {

		$version = $this->memcached->get($this->getModuleVersionKey($module));

		if ($version === false) {
			$version = 1;
			$this->memcached->add($this->getModuleVersionKey($module), $version);
		}

		return $version;
	}

	/**
	 * @param string $module
	 * @return string
	 */
	private function getModuleVersionKey($module) {
		return static::$sCachePrefix . ':' . $module . ':version';
	}

}
