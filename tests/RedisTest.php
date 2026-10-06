<?php
namespace PhpCache;

require_once 'vendor/autoload.php';
require_once 'src/PhpCache.php';
require_once 'tests/Privateer.php';

class RedisTest extends \PHPUnit\Framework\TestCase {

    use \Assets\Privateer;

    /**
     * @var Redis
     */
    protected $cache;

    protected function setUp(): void {
        $this->cache = new Redis();
    }

    public function testCorrectInstance() {
        $this->assertInstanceOf('PhpCache\Redis', $this->cache);
    }

    public function testClientCreated() {
        $data = self::getProperty($this->cache, 'redis');
        $this->assertInstanceOf('Predis\Client', $data);
    }

    public function testKeyFormat() {
        $key = new CacheKey('test', 'prop');

        $this->assertEquals('PhpCache:test:prop', self::callMethod($this->cache, 'getKey', array($key)));
    }

    public function testKeyStoredWithColonSeparator() {
        $key = new CacheKey('test', rand(1,1000));
        $this->cache->set($key, 'Lorem ipsum');

        $redis = self::getProperty($this->cache, 'redis');
        $this->assertEquals(1, $redis->exists('PhpCache:test:' . $key->getProperty()));

        $this->cache->clear($key);
    }

    public function testClearModule() {
        $key1 = new CacheKey('test', 'prop1');
        $key2 = new CacheKey('test', 'prop2');
        $other = new CacheKey('other', 'prop1');

        $this->cache->set($key1, 'a');
        $this->cache->set($key2, 'b');
        $this->cache->set($other, 'c');

        $this->cache->clearModule($key1);

        $this->assertFalse($this->cache->check($key1));
        $this->assertFalse($this->cache->check($key2));
        $this->assertTrue($this->cache->check($other));

        $this->cache->clear($other);
    }

    public function testFlush() {
        $key = new CacheKey('test', rand(1,1000));
        $this->cache->set($key, 'Lorem ipsum');

        $this->assertTrue($this->cache->check($key));

        $this->cache->clearAll();
        $this->assertFalse($this->cache->check($key));
    }


    /**
     * @dataProvider getSetProvider
     *
     * @param $in
     * @param $out
     */
    public function testGetSet($in, $out) {
        $key = new CacheKey('test', rand(1,1000));

        $this->cache->set($key, $in);

        $this->assertTrue($this->cache->check($key));

        $this->assertEquals($out, $this->cache->get($key));

        $this->cache->clear($key);

        $this->assertEquals(false, $this->cache->get($key));
        $this->assertFalse($this->cache->check($key));

    }

    public function testArray() {

        $data = array('a' => 'a', 'b' => 'b');

        $key = new CacheKey('test', rand(1,1000));

        $this->cache->set($key, $data);

        $val = $this->cache->get($key);

        $this->assertEquals($data, $val);

    }

    public function testStdClass() {

        $data = new \stdClass();
        $data->a = 'a';
        $data->b = 'b';

        $key = new CacheKey('test', rand(1,1000));

        $this->cache->set($key, $data);

        $val = $this->cache->get($key);

        $this->assertEquals($data, $val);

    }

    public function getSetProvider() {
        return array(
            array(true, true),
            array('true', 'true'),
            array('false', 'false'),
            array('', ''),
            array(12, 12),
            array(0.5, 0.5)
        );
    }

}