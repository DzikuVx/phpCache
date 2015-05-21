<?php

namespace PhpCache;

/**
 * APC wrapper
 * @author Paweł Spychalski 2013
 * @version 2.0.2
 */
class Apc {

	/**
	 * key prefix
	 * @var string
	 */
	static private $sCachePrefix = 'phpCache';

	/**
	 * @var Apc
	 */
	private static $instance;

	public static function getInstance() {
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	/**
	 * Set key prefix
	 * @param string $prefix
	 */
	static public function sSetPrefix($prefix) {
		self::$sCachePrefix = $prefix;
	}

	private function __construct() {
	}

	/**
	 * @return string
	 */
	static public function sGetPrefix() {
		return self::$sCachePrefix;
	}

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

		$tValue = $this->get($key);
			
		if ($tValue === false) {
			return false;
		}else {
			return true;
		}
	}

	/**
	 * Get cache value
	 * @param CacheKey $key
	 * @return mixed
	 */
	public function get(CacheKey $key) {
		return apc_fetch($this->getKey($key));
	}

	/**
	 * Unset cache value
	 * @param CacheKey $key
	 */
	public function clear(CacheKey $key) {
		apc_delete($this->getKey($key));
	}

	/**
	 * @param CacheKey $key
	 */
	public function clearModule(CacheKey $key) {

		$module = $key->getModule();
		
		$iterator = new \APCIterator('user');
		while ($iterator->current()) {

			$tKey = $iterator->key();

			if (mb_strpos ( $tKey, $module . '||' ) !== false) {
				apc_delete($tKey);
			}
			$iterator->next();
		}

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

		apc_store ( $this->getKey($key) , $value , $sessionLength);
	}

	/**
	 * @param string $className
	 */
	public function clearClassCache($className = null) {

		$iterator = new \APCIterator('^user^');
		while ($iterator->current()) {

			$tKey = $iterator->key();

			if (mb_strpos ( $tKey, $className . '::' ) !== false) {
				apc_delete($tKey);
			}
			$iterator->next();
		}

	}

	public function clearAll() {
		apc_clear_cache();
	}

	/**
	* @param CacheKey $key
	* @return string
	*/
	private function getKey(CacheKey $key) {
		return self::$sCachePrefix.'__'.$key->getModule().'||'.$key->getProperty();
	}
}