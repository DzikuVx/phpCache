<?php

namespace phpCache;

/**
 * @deprecated
 */
class Session {
	private $size;
	private $currentSize = 0;
	private $timeThreshold = 60;
	private $cacheName = 'cache';
	private $cacheMaintenanceTimeName = 'cacheMaintenanceTime';

	/**
	 * @var Session
	 */
	private static $instance;

	/**
	 * @return Session
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}
	
	private function maintenace(CacheKey $key) {

		$module = $key->getModule();
		
		if (! isset ( $_SESSION [$this->cacheName] [$module] )) {
			return false;
		}
			
		//Sprawdz, czy wykonać czyszczenie
		if (time () < $_SESSION [$this->cacheMaintenanceTimeName] [$module]) {
			return false;
		}
			
		//Ustaw czas następnego czyszczenia
		$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;

		//Pobierz wszystkie klucze w module
		$keys = array_keys ( $_SESSION [$this->cacheName] [$module] );

		//Wykonaj pętlę po kluczach
		foreach ( $keys as $value ) {
			//Oczyść przeterminowane klucze
			if (time () > $_SESSION [$this->cacheName] [$module] [$value] ['time']) {
				unset ( $_SESSION [$this->cacheName] [$module] [$value] );
			}
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getTimeThreshold() {
		return $this->timeThreshold;
	}

	/**
	 * @param int $timeThreshold
	 */
	public function setTimeThreshold($timeThreshold) {
		$this->timeThreshold = $timeThreshold;
	}

	/**
	 * Konstruktor
	 *
	 * @param int $size - rozmiar cache
	 * @return boolean
	 */
	private function __construct($size = 100) {
		$this->size = $size;

		return true;
	}

	public function check(CacheKey $key) {
		if (isset ( $_SESSION [$this->cacheName] [$key->getModule()] [$key->getProperty()] )) {
			return true;
		} else {
			return false;
		}
	}

	function get(CacheKey $key) {
		
		$module = $key->getModule();
		$id 	= $key->getProperty();
		
		if (isset ( $_SESSION [$this->cacheName] [$module] [$id] )) {
			$tValue = $_SESSION [$this->cacheName] [$module] [$id] ['value'];
			$this->maintenace($key);
			return $tValue;
		} else {
			return NULL;
		}
	}

	/**
	 * 
	 * @param CacheKey $key
	 * @param mixed $value
	 * @param int $expire
	 */
	function set(CacheKey $key, $value, $expire = null) {

		$module = $key->getModule();
		$id 	= $key->getProperty();
		
		if ($expire == null) {
			$expire = $this->timeThreshold;
		}
		
		$_SESSION [$this->cacheName] [$module] [$id] ['value'] = $value;
		$_SESSION [$this->cacheName] [$module] [$id] ['time'] = time () + $expire;

		if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
			$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;
		}

		$this->currentSize += 1;
	}

	public function clear(CacheKey $key) {
		
		$module = $key->getModule();
		$id		= $key->getProperty();
		
		if (!empty($id)) {
			unset ( $_SESSION [$this->cacheName] [$module] [$id] );
		} else {
			unset ( $_SESSION [$this->cacheName] [$module] );
		}
	}
	
	public function clearAll() {
		$_SESSION [$this->cacheName] = array();
	}
	
}