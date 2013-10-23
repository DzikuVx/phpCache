<?php

namespace Cache;

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