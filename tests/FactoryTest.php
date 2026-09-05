<?php
namespace PhpCache;

require_once 'src/PhpCache.php';

class PhpCacheTest extends \PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        $oReflection = new \ReflectionClass(PhpCache::class);
        $oProperty = $oReflection->getProperty('instance');
        $oProperty->setAccessible(true);
        $oProperty->setValue(null, null);
    }

    public function testCreateFactory() {
        $oFactory = PhpCache::getInstance();
        $this->assertInstanceOf('PhpCache\PhpCache', $oFactory);
    }

    public function testUnexistingConnector() {
        $this->expectException(\PhpCache\Exception::class);
        $oFactory = PhpCache::getInstance();
        $oFactory->init('Malina');
    }

    public function testReInitialisationThrows() {
        $oFactory = PhpCache::getInstance();
        $oFactory->init('Variable');

        $this->expectException(\PhpCache\Exception::class);
        $oFactory->init('Variable');
    }

    private function processConnector($sName) {
        $oFactory = PhpCache::getInstance();
        $oCache = $oFactory->init($sName)->getCache();

        $this->assertInstanceOf('phpCache\\' . $sName, $oCache);

        $oCache->clearAll();

        $aKeys = array();
        $aKeys[0] = new CacheKey('Test1');
        $aKeys[1] = new CacheKey('Test1', 'Prop1');
        $aKeys[2] = new CacheKey($oFactory);
        $aKeys[3] = new CacheKey($oFactory, 'Prop2');

        foreach($aKeys as $oKey) {
            $this->assertInstanceOf('phpCache\CacheKey', $oKey);

            $this->assertFalse($oCache->check($oKey));

            $sValue = 'test Value';
            $oCache->set($oKey, $sValue);

            $this->assertTrue($oCache->check($oKey));
            $this->assertEquals($sValue, $oCache->get($oKey));

            $oCache->clear($oKey);

            $this->assertFalse($oCache->check($oKey));
            $this->assertFalse($oCache->get($oKey));

            $aSetValue = array(1 => 2, 'key' => 'This is key');

            $oCache->set($oKey, $aSetValue);

            $aGet = $oCache->get($oKey);

            $this->assertIsArray($aGet);

            $this->assertArrayHasKey(1, $aGet);
            $this->assertEquals(2, $aGet[1]);

            $this->assertArrayHasKey('key', $aGet);
            $this->assertEquals('This is key', $aGet['key']);

            $oCache->clear($oKey);

            $aSetValue = new \stdClass();
            $aSetValue->key1 = 2;
            $aSetValue->key2 = 'This is key';

            $oCache->set($oKey, $aSetValue);

            $aGet = $oCache->get($oKey);

            $this->assertInstanceOf('\stdClass', $aGet);
            $this->assertEquals(2, $aGet->key1);
            $this->assertEquals('This is key', $aGet->key2);

            $oCache->clear($oKey);
        }

    }

    public function testMemcached() {
        $this->processConnector('Memcached');
    }

    public function testFile() {
        $this->processConnector('File');
    }

    public function testSession() {
        $this->processConnector('Session');
    }

    public function testVariable() {
        $this->processConnector('Variable');
    }

    public function testRedis() {
        $this->processConnector('Redis');
    }

    public function testDummyDoesNotCache() {
        $oFactory = PhpCache::getInstance();
        $oCache = $oFactory->init('Dummy')->getCache();

        $this->assertInstanceOf('phpCache\Dummy', $oCache);

        $oKey = new CacheKey('Test1', 'Prop1');

        $this->assertFalse($oCache->check($oKey));

        $oCache->set($oKey, 'test Value');

        $this->assertFalse($oCache->check($oKey));
        $this->assertFalse($oCache->get($oKey));

        $oCache->clear($oKey);
        $oCache->clearAll();
    }

    public function testDummyIsDefaultMechanism() {
        $this->assertEquals('Dummy', PhpCache::$sDefaultMechanism);

        $oFactory = PhpCache::getInstance();
        $oCache = $oFactory->init()->getCache();

        $this->assertInstanceOf('phpCache\Dummy', $oCache);
    }

}
 