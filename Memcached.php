<?php

namespace Cache;

class Memcached{

	/**
	 * Domyślny czas ważności cache [s]
	 *
	 * @var int
	 */
	private $timeThreshold = 7200;

	/**
	 * Czy dokonuwać kompresji pliku cache
	 *
	 * @var boolean
	 */
	private $useZip = false;

	/**
	 * Obiekt memcached
	 * @var Memcache
	 */
	private $memcached = null;

	/**
	 * @var Memcached
	 */
	private static $instance;

	/**
	 * @return Memcached
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * @var array
	 */
	private $internalCache = array();

	static public $host = '127.0.0.1';
	static public $port = 11211;

	/**
	 * Konstruktor
	 */
	private function __construct() {
		$this->memcached = new \Memcache();
		$this->memcached->connect(self::$host, self::$port);
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

		$sKey = $this->getKey($key);

		if (!isset($this->internalCache[$sKey])) {
			$this->internalCache[$sKey] = $this->memcached->get($sKey);
		}

		return $this->internalCache[$sKey];
	}

	/**
	 * Unset cache value
	 * @param CacheKey $key
	 */
	function clear(CacheKey $key) {
		$this->memcached->delete($this->getKey($key));
	}

	public function clearModule(CacheKey $key) {
		$this->memcached->flush();
	}
	
	public function set(CacheKey $key, $value, $sessionLength = null) {

		if ($sessionLength == null) {
			$sessionLength = $this->timeThreshold;
		}

		$this->memcached->set($this->getKey($key), $value, $this->useZip, $sessionLength);
	}

	/**
	 * Wyczyszczenie wpisów zależnych od podanej klasy
	 *
	 * @param string $className
	 */
	public function clearClassCache($className = null) {

		$this->memcached->flush();
	}

	/**
	 * Oczyszczenie całego cache
	 */
	public function clearAll() {
		$this->memcached->flush();
	}

	/**
	 * Setup key for memcached
	 * @param CacheKey $key
	 * @return string
	 */
	private function getKey(CacheKey $key) {
		return $key->getModule() . '::' . $key->getProperty();
	}

}