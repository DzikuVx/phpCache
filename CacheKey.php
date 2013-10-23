<?php

namespace Cache;

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
		if (is_object($module)) {
			$this->module = get_class($module);
		}else {
			$this->module = (string) $module;
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