<?php

namespace Cache;

require_once dirname ( __FILE__ ) . '/Debug.php';
\General\Debug::create();

$aRegisteredMechanisms = array('Apc', 'File', 'Memcached', 'Session', 'Variable');

require_once dirname ( __FILE__ ) . '/../Factory.php';

echo "Load factory: OK \n";

try {
	$oFactory = Factory::getInstance();
	
	if (empty($oFactory)) {
		echo "Create factory object: FAILED\n";
		die();
	}
	
	echo "Create factory object: OK\n";
	
} catch (\Exception $e) {
	echo "Create factory object: FAILED\n";
	die();
}

try {
	$oCache = $oFactory->create('Malina');
	
	echo "Detect unknown mechanism: FAILED\n";
	die();
	
} catch (\Exception $e) {
	echo "Detect unknown mechanism: OK\n";
}

/**
 * Start actual tests
 */

foreach ($aRegisteredMechanisms as $sMethod) {
	
	echo "\n----------" . $sMethod . "----------\n";
	
	try {
		
		$oCache = $oFactory->create($sMethod);
		
		if (empty($oCache)) {
			echo "Create cache {$sMethod} object: FAILED\n";
			continue;
		}
		
		echo "Create cache {$sMethod} object: OK\n";
		
	}catch (\Exception $e) {
		echo "Create cache {$sMethod} object: FAILED\n";
		continue;
	}
	
	try {
		
		$oCache->clearAll();
		echo "{$sMethod} clearAll: OK\n";
		
	} catch (\Exception $e) {
		echo "{$sMethod} clearAll: FAILED\n";
		continue;
	}
	
}
