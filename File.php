<?php

namespace phpCache;

/**
 * Shared cache over file system. 
 * Precaution: rather should not be used. This is rather last resort
 * @author pawel
 *
 */
class File{

	/**
	 * Tablica wpisów w cache
	 *
	 * @var array
	 */
	private $elements = array ();

	/**
	 * Nazwa pliku przechowującego cache
	 *
	 * @var string
	 */
	private $fileName = null;

	/**
	 * Domyślny czas ważności cache [s]
	 *
	 * @var int
	 */
	private $timeThreshold = 1200;

	/**
	 * Maksymalny rozmiar cache
	 *
	 * @var int
	 */
	private $maxSize = 2000;

	/**
	 * Obecny rozmiar
	 *
	 * @var int
	 */
	private $currentSize = 0;

	/**
	 * Czy zawartość cache zmieniła się po załadowaniu/utworzeniu
	 *
	 * @var boolean
	 */
	private $changed = false;

	/**
	 * Nazwa wpisu w $_SESSION przechowującego następny czas oczyszczania
	 *
	 * @var string
	 */
	private $cacheMaintenanceTimeName = 'CacheOverFileMaintnance';

	/**
	 * Czy dokonuwać kompresji pliku cache
	 *
	 * @var boolean
	 */
	private $useZip = true;

	/**
	 * @var File
	 */
	private static $instance;

	/**
	 * @return File
	 */
	public static function getInstance(){
		if (empty(self::$instance)) {
			$className = __CLASS__;
			self::$instance = new $className;
		}
		return self::$instance;
	}

	public function __destruct() {
		$this->synchronize ();
	}

	private function __construct() {
		$this->fileName = dirname ( __FILE__ ) . "/userData/" . get_class () . '.sca';
		$this->load ();
	}

	/**
	 * Pobranie cache
	 */
	private function load() {

		if (file_exists ( $this->fileName )) {
			$tCounter = 0;
			$tFile = fopen ( $this->fileName, 'r' );

			/*
			 * Załóż blokadę na plik cache
			 */
			while ( ! flock ( $tFile, LOCK_SH ) ) {
				usleep ( 5 );
				$tCounter ++;
				if ($tCounter == 100) {
					return false;
				}
			}

			$tContent = fread ( $tFile, filesize ( $this->fileName ) );

			if ($this->useZip) {
				$tContent = gzuncompress ( $tContent );
			}

			$this->elements = unserialize ( $tContent );

			flock ( $tFile, LOCK_UN );
			fclose ( $tFile );

			$tKeys = array_keys ( $this->elements );
			foreach ( $tKeys as $tKey ) {
				$this->maintenace ( new CacheKey($tKey) );
			}
				
		}
			
		return true;
	}

	/**
	 * Synchronize cache with file
	 *
	 * @return boolean
	 */
	function synchronize() {

		$tCounter = 0;

		if ($this->changed) {
			$tFile = fopen ( $this->fileName, 'a' );

			/*
			 * Załóż blokadę na plik cache
			 */
			while ( ! flock ( $tFile, LOCK_EX ) ) {
				usleep ( 5 );
				$tCounter ++;
				if ($tCounter == 100) {
					return false;
				}
			}

			/*
			 * Jeśli udało się założyć blokadę, zapisz elementy
			 */
			$tContent = serialize ( $this->elements );

			ftruncate ( $tFile, 0 );

			if ($this->useZip) {
				$tContent = gzcompress ( $tContent );
			}

			fputs ( $tFile, $tContent );

			flock ( $tFile, LOCK_UN );
			fclose ( $tFile );
			return true;
		}
			
		return true;
	}

	/**
	 * Cache mainanace, removed old entries
	 * @param CacheKey $key
	 */
	private function maintenace(CacheKey $key) {

		$module = $key->getModule();
		
		if (! isset ( $this->elements [$module] )) {
			return false;
		}

		if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
			$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time ();
		}

		//Sprawdz, czy wykonać czyszczenie
		if (time () < $_SESSION [$this->cacheMaintenanceTimeName] [$module]) {
			return false;
		}
			
		//Ustaw czas następnego czyszczenia
		$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $this->timeThreshold;

		//Pobierz wszystkie klucze w module
		$keys = array_keys ( $this->elements [$module] );

		//Wykonaj pętlę po kluczach
		foreach ( $keys as $value ) {
			//Oczyść przeterminowane klucze
			if (time () > $this->elements [$module] [$value]->getTime ()) {
				unset ( $this->elements [$module] [$value] );
				$this->changed = true;
			}
		}

		return true;
	}

	/**
	 * Check is cache entry is set
	 * @param CacheKey $key
	 * @return boolean
	 */
	function check(CacheKey $key) {

		if (isset ( $this->elements [$key->getModule()] [$key->getProperty()] )) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get value from cache or null when not set
	 * @param CacheKey $key
	 * @return mixed
	 */
	public function get(CacheKey $key) {

		if (isset ( $this->elements [$key->getModule()] [$key->getProperty()] )) {
			$tValue = $this->elements [$key->getModule()] [$key->getProperty()]->getValue ();
			return $tValue;
		} else {
			return false;
		}
	}

	/**
	 * Unset cache value
	 * @param CacheKey $key
	 */
	public function clear(CacheKey $key) {
		if (isset ( $this->elements [$key->getModule()] [$key->getProperty()] )) {
			unset ( $this->elements [$key->getModule()] [$key->getProperty()] );
			$this->changed = true;
		}
	}

	/**
	 * Clear whole module and all it's properties
	 * @param CacheKey $key
	 */
	function clearModule(CacheKey $key) {

		if (isset ( $this->elements [$key->getModule()] )) {
			unset ( $this->elements [$key->getModule()] );
			$this->changed = true;
		}

	}

	public function set(CacheKey $key, $value, $sessionLength = null) {

		$module 	= $key->getModule();
		$property 	= $key->getProperty();
		
		if ($sessionLength == null) {
			$sessionLength = $this->timeThreshold;
		}

		if (! isset ( $this->elements [$module] [$property] )) {
			$this->elements [$module] [$property] = new FileElement ( $value, time () + $sessionLength );
		} else {
			$this->elements [$module] [$property]->set ( $value, time () + $sessionLength);
		}

		/*
		 * Określ czas następnego czyszczenia cache dla tego modułu
		 */
		if (! isset ( $_SESSION [$this->cacheMaintenanceTimeName] [$module] )) {
			$_SESSION [$this->cacheMaintenanceTimeName] [$module] = time () + $sessionLength;
		}

		$this->changed = true;
	}

	/**
	 * Wyczyszczenie wpisów zależnych od podanej klasy
	 *
	 * @param string $className
	 */
	public function clearClassCache($className) {

		$tKeys = array_keys ( $this->elements );
		foreach ( $tKeys as $tKey ) {
			if (mb_strpos ( $tKey, $className . '::' ) !== false) {
				$this->clearModule ( $tKey );
			}
		}
	}

	/**
	 * Oczyszczenie całego cache
	 */
	public function clearAll() {
		$this->elements = array();
	}

}

/**
 * Klasa elementów cache współdzielonego
 *
 * @author Paweł Spychalski <pawel@spychalski.info>
 * @see http://www.spychalski.info
 * @see CacheOverFile
 * @category Common
 * @version 0.9
 */
class FileElement{
	/**
	 * Wartość wpisu
	 *
	 * @var mixed
	 */
	protected $value = null;

	/**
	 * Czas ważności wpisu
	 *
	 * @var int
	 */
	protected $time = null;

	/**
	 * @return int
	 */
	public function getTime() {

		return $this->time;
	}

	/**
	 * @param int $time
	 */
	public function setTime($time) {
		$this->time = $time;
	}

	/**
	 * @return mixed
	 */
	public function getValue() {

		return $this->value;
	}

	/**
	 * @param mixed $value
	 */
	public function setValue($value) {
		$this->value = $value;
	}

	function __construct($value, $time) {
		$this->value = $value;
		$this->time = $time;
	}

	public function set($value, $time) {
		$this->value = $value;
		$this->time = $time;
	}

}