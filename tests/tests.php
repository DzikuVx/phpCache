<?php

namespace Cache;

$bSuccess = true;

require_once dirname ( __FILE__ ) . '/Debug.php';
\General\Debug::create();

$aRegisteredMechanisms = array('Apc', 'File', 'Memcached', 'Session', 'Variable');

require_once dirname ( __FILE__ ) . '/../Factory.php';

echo "Load factory: OK \n";

try {
	$oFactory = Factory::getInstance();
	
	if (empty($oFactory)) {
		echo "Create factory object: FAILED\n";
		$bSuccess = false;
		die();
	}
	
	echo "Create factory object: OK\n";
	
} catch (\Exception $e) {
	echo "Create factory object: FAILED\n";
	$bSuccess = false;
	die();
}

try {
	$oCache = $oFactory->create('Malina');
	
	echo "Detect unknown mechanism: FAILED\n";
	$bSuccess = false;
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
			$bSuccess = false;
			continue;
		}
		
		echo "Create cache {$sMethod} object: OK\n";
		
	}catch (\Exception $e) {
		echo "Create cache {$sMethod} object: FAILED\n";
		throw new \Exception($e->getMessage(), $e->getCode(), $e);
		$bSuccess = false;
		continue;
	}
	
	try {
		
		$oCache->clearAll();
		echo "{$sMethod} clearAll: OK\n";
		
	} catch (\Exception $e) {
		echo "{$sMethod} clearAll: FAILED\n";
		$bSuccess = false;
		continue;
	}
	
	$aKeys = array();
	$aKeys[0] = new CacheKey('Test1');
	$aKeys[1] = new CacheKey('Test1', 'Prop1');
	$aKeys[2] = new CacheKey($oFactory);
	$aKeys[3] = new CacheKey($oFactory, 'Prop2');
	
	echo "{$sMethod} create keys: OK\n";
	
	foreach ($aKeys as $iIndex => $oKey) {

		if ($oCache->check($oKey) === false) {
			echo "{$sMethod} check empty key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} check empty key #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		$oCache->set($oKey, 'test Value');
		echo "{$sMethod} set key #{$iIndex}: OK\n";
		
		if ($oCache->check($oKey) === true) {
			echo "{$sMethod} check not empty key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} check not empty key #{$iIndex}: FAILED : ";
			var_dump($oCache->check($oKey));
			$bSuccess = false;
		}
		
		if ($oCache->get($oKey) === 'test Value') {
			echo "{$sMethod} get not empty key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get not empty key #{$iIndex}: FAILED : ";
			var_dump($oCache->get($oKey));
			$bSuccess = false;
		}
		
		$oCache->clear($oKey);
		echo "{$sMethod} clear key #{$iIndex}: OK\n";
		
		if ($oCache->check($oKey) === false) {
			echo "{$sMethod} check cleared key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} check cleared key #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
	}
	
}

echo "\n\n----------------------------------\n";
if ($bSuccess) {
	echo "STATUS: OK\n";
} else {
	echo "STATUS: FAILED\n";
}
echo "----------------------------------\n";