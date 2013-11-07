<?php

namespace phpCache;

/**
 * Entries are stored in an PHP variable. They are not available between requests
 */
class Variable {

	/**
	 * @var array
	 */
	private $cache = array ();

	/**
	 * @var Variable
	 */
	private static $instance;

	public function clearAll() {
	}

	/**
	 * 
	 * @return \phpCache\Variable
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	private function __construct() {
	}

	/**
	 * Set cache value
	 *
	 * @param CacheKey $key
	 * @param mixed $value
	 * @param int $sessionLength
	 */
	public function set(CacheKey $key, $value, $sessionLength = null) {

		$this->cache [$key->getModule()] [$key->getProperty()] = $value;

		return true;
	}

	/**
	 * Check if cache entry exist
	 * @param CacheKey $key
	 * @return boolean
	 */
	public function check(CacheKey $key) {

		if (isset ($this->cache [$key->getModule()] [$key->getProperty()])) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get cache value
	 * @param CacheKey $key
	 * @return mixed
	 */
	public function get(CacheKey $key) {

		if (isset ( $this->cache [$key->getModule()] [$key->getProperty()] )) {
			return $this->cache [$key->getModule()] [$key->getProperty()];
		} else {
			return null;
		}
	}

	/**
	 * Unset cache value
	 * @param CacheKey $key
	 */
	public function clear(CacheKey $key) {
		unset($this->cache [$key->getModule()] [$key->getProperty()]);
	}

}
