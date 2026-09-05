<?php

namespace PhpCache;

/**
 * Cache method factory
 * Static
 * @author Paweł
 */
class PhpCache {

	/**
	 * Name of default caching mechanism
	 * @var string
	 */
	public static $sDefaultMechanism = 'Dummy';

	/**
	 * Array of registered and available caching mechanisms
	 * @var array
	 */
	private $aRegisteredMechanisms = array('Dummy', 'File', 'Memcached', 'Session', 'Variable', 'Redis');

	/**
	 * The initialised caching mechanism object
	 * @var Dummy|File|Memcached|Session|Variable|Redis|null
	 */
	private $oCacheInstance = null;

	private static ?PhpCache $instance;

	/**
	 * Private constructor
	 */
	private function __construct() {

	}

    /**
     * Private __clone magic method
     */
    private function __clone() {

    }

	/**
	 * Initialise caching mechanism according to passed name and connection settings
	 * @param string|null $sMethod Defaults to self::$sDefaultMechanism when omitted
	 * @param string|null $sHost
	 * @param int|null $iPort
	 * @param int|null $iDatabase
	 * @return PhpCache
     * @throws Exception
	 */
	public function init($sMethod = null, $sHost = null, $iPort = null, $iDatabase = null) {

		if ($this->oCacheInstance !== null) {
			throw new Exception('Caching mechanism already initialised');
		}

		if ($sMethod === null) {
			$sMethod = static::$sDefaultMechanism;
		}

		/*
		 * check if passed name is an registered method
		 */
		if (array_search($sMethod, $this->aRegisteredMechanisms) === false) {
			throw new Exception('Unknown caching mechanism');
		}

        /** @noinspection PhpIncludeInspection */
        require_once dirname ( __FILE__ ) . '/' . $sMethod . '.php';

		$sClassName = '\phpCache\\' . $sMethod;

		if ($sHost !== null && property_exists($sClassName, 'host')) {
			$sClassName::$host = $sHost;
		}

		if ($iPort !== null && property_exists($sClassName, 'port')) {
			$sClassName::$port = $iPort;
		}

		if ($iDatabase !== null && property_exists($sClassName, 'db')) {
			$sClassName::$db = $iDatabase;
		}

        /** @noinspection PhpUndefinedMethodInspection */
        $this->oCacheInstance = new $sClassName();

		return $this;
	}

	/**
	 * Return the initialised caching mechanism object
	 * @return File,Memcached,Session,Variable,Redis
     * @throws Exception
	 */
	public function getCache() {

		if ($this->oCacheInstance === null) {
			throw new Exception('Caching mechanism not initialised, call init() first');
		}

		return $this->oCacheInstance;
	}

	/**
	 * get factory instance
	 * @return PhpCache
	 */
	static public function getInstance() {

		if (empty(self::$instance)) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

/**
 * 
 * Class providing caching key functionality
 * 
 * @author pawel
 *
 */
class CacheKey {

	/**
	 * @var string
	 */
	private $module = '';

	/**
	 * @var string
	 */
	private $property = '';

	/**
	 * @param mixed $module
	 * @param string $property
	 */
	public function __construct($module, $property = null) {
		$this->setModule($module);
		$this->setProperty($property);
	}

	/**
	 * Set module property
	 * @param mixed $value
	 */
	public function setModule($value) {
		if (is_object($value)) {
			$this->module = get_class($value);
		}else {
			$this->module = (string) $value;
		}
	}

	/**
	 * Set value property
	 * @param string $value
	 */
	public function setProperty($value) {
		$this->property = (string) $value;
	}

	/**
	 * @return string
	 */
	public function getModule() {
		return $this->module;
	}

	/**
	 * @return string
	 */
	public function getProperty() {
		return $this->property;
	}
}

class Exception extends \Exception {

}