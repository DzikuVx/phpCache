<?php
namespace PhpCache;

require_once 'vendor/autoload.php';
require_once 'src/PhpCache.php';
require_once 'tests/Privateer.php';

class MemcachedTest extends \PHPUnit\Framework\TestCase {

    use \Assets\Privateer;

    /**
     * @var Memcached
     */
    protected $cache;

    protected function setUp(): void {
        if (!extension_loaded('memcached')) {
            $this->markTestSkipped('memcached extension is not available');
        }

        $this->cache = new Memcached();
        $this->cache->clearAll();
    }

    public function testCorrectInstance() {
        $this->assertInstanceOf('PhpCache\Memcached', $this->cache);
    }

    public function testModuleVersionKeyFormat() {
        $this->assertEquals('PhpCache:test:version', self::callMethod($this->cache, 'getModuleVersionKey', array('test')));
    }

    public function testKeyFormat() {
        $key = new CacheKey('test', 'prop');

        $this->assertEquals('PhpCache:test:prop:v1', self::callMethod($this->cache, 'getKey', array($key)));
    }

    public function testKeyStoredWithColonSeparator() {
        $key = new CacheKey('test', rand(1,1000));
        $this->cache->set($key, 'Lorem ipsum');

        $memcached = self::getProperty($this->cache, 'memcached');
        $this->assertEquals('Lorem ipsum', $memcached->get('PhpCache:test:' . $key->getProperty() . ':v1'));
    }

    public function testClearModuleBumpsVersion() {
        $key = new CacheKey('test', 'prop');
        $other = new CacheKey('other', 'prop');

        $this->cache->set($key, 'a');
        $this->cache->set($other, 'b');

        $this->cache->clearModule($key);

        $this->assertEquals('PhpCache:test:prop:v2', self::callMethod($this->cache, 'getKey', array($key)));
        $this->assertFalse($this->cache->check($key));
        $this->assertTrue($this->cache->check($other));
    }

    public function testGetSet() {
        $key = new CacheKey('test', rand(1,1000));

        $this->cache->set($key, 'Lorem ipsum');
        $this->assertTrue($this->cache->check($key));
        $this->assertEquals('Lorem ipsum', $this->cache->get($key));

        $this->cache->clear($key);
        $this->assertFalse($this->cache->check($key));
    }

}
