<?php

namespace phpCache;

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

		//Test simple values
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
			$bSuccess = false;
		}
		
		if ($oCache->get($oKey) === 'test Value') {
			echo "{$sMethod} get not empty key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get not empty key #{$iIndex}: FAILED : ";
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
		
		if ($oCache->get($oKey) === false) {
			echo "{$sMethod} get cleared key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get cleared key #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		//Test arrays
		$aSetValue = array(1 => 2, 'key' => 'This is key');
		
		$oCache->set($oKey, $aSetValue);
		echo "{$sMethod} set key for array #{$iIndex}: OK\n";
		
		$aGet = $oCache->get($oKey);
		
		if (!empty($aGet) && is_array($aGet)) {
			echo "{$sMethod} get array key #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get array key #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		if ($aGet[1] === 2) {
			echo "{$sMethod} get array Integer element #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get array Integer element #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		if ($aGet['key'] === 'This is key') {
			echo "{$sMethod} get array String element #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get array String element #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		$oCache->clear($oKey);
		
		//Test stdClass
		$aSetValue = new \stdClass();
		$aSetValue->key1 = 2;
		$aSetValue->key2 = 'This is key';
		
		$oCache->set($oKey, $aSetValue);
		echo "{$sMethod} set key for stdClass #{$iIndex}: OK\n";
		
		$aGet = $oCache->get($oKey);
		
		if (!empty($aGet) && is_object($aGet)) {
			echo "{$sMethod} get stdClass #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get stdClass #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		if ($aGet->key1 === 2) {
			echo "{$sMethod} get stdClass Integer element #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get stdClass Integer element #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		if ($aGet->key2 === 'This is key') {
			echo "{$sMethod} get stdClass String element #{$iIndex}: OK\n";
		} else {
			echo "{$sMethod} get stdClass String element #{$iIndex}: FAILED\n";
			$bSuccess = false;
		}
		
		$oCache->clear($oKey);
		
	}
	
}

echo "\n\n----------------------------------\n";
if ($bSuccess) {
	echo "STATUS: OK\n";
} else {
	echo "STATUS: FAILED\n";
}
echo "----------------------------------\n";