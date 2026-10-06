<?php
namespace PhpCache;

require_once 'vendor/autoload.php';
require_once 'src/PhpCache.php';
require_once 'tests/Privateer.php';
require_once 'tests/Fixtures/Baz.php';

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

    /**
     * Modules whose names contain Redis glob metacharacters (\ * ? [ ]) or colons
     *
     * @return array
     */
    private function getSpecialModules() {
        return array(
            'class' => new \Foo\Bar\Baz(),
            'string' => 'Foo\Bar\Baz',
            'colons' => 'Foo::bar',
            'star' => 'a*b',
            'question' => 'a?b',
            'brackets' => 'a[1]',
            'escaped star' => 'a\*b',
            'unrelated' => 'unrelated',
        );
    }

    public function clearModuleProvider() {
        $data = array();
        foreach ($this->getSpecialModules() as $name => $module) {
            $data[$name] = array($name);
        }
        return $data;
    }

    /**
     * Set two keys in every special module, clear one module, check that only its keys are gone
     *
     * @param string $target
     */
    private function assertClearModuleOnlyClearsTarget($target) {
        $modules = $this->getSpecialModules();
        $this->cache->clearAll();

        $keys = array();
        foreach ($modules as $name => $module) {
            // "class" and "string" are the same module, so give them distinct properties
            $keys[$name] = array(new CacheKey($module, $name . '1'), new CacheKey($module, $name . '2'));
            foreach ($keys[$name] as $key) {
                $this->cache->set($key, 'value');
            }
        }

        $this->cache->clearModule(new CacheKey($modules[$target]));

        $targetModule = $keys[$target][0]->getModule();
        foreach ($keys as $name => $moduleKeys) {
            foreach ($moduleKeys as $key) {
                if ($key->getModule() === $targetModule) {
                    $this->assertFalse($this->cache->check($key), "Key '{$key->getModule()}' / '{$key->getProperty()}' should be cleared");
                } else {
                    $this->assertTrue($this->cache->check($key), "Key '{$key->getModule()}' / '{$key->getProperty()}' should be kept");
                }
            }
        }

        $this->cache->clearAll();
    }

    /**
     * @dataProvider clearModuleProvider
     *
     * @param string $target
     */
    public function testClearModuleWithSpecialCharacters($target) {
        $this->assertClearModuleOnlyClearsTarget($target);
    }

    public function prefixProvider() {
        return array(
            'brackets' => array('My[App]', 'MyA'),
            'star' => array('App*', 'AppX'),
            'question and backslash' => array('a\b?', 'ab!'),
        );
    }

    /**
     * @dataProvider prefixProvider
     *
     * @param string $prefix Prefix containing glob characters
     * @param string $lookalike Prefix the unescaped glob would also match
     */
    public function testClearModuleWithSpecialPrefix($prefix, $lookalike) {
        $originalPrefix = Redis::sGetPrefix();

        try {
            $key = new CacheKey('Foo\Bar\Baz', 'prop');

            Redis::sSetPrefix($lookalike);
            $this->cache->set($key, 'lookalike');

            foreach (array_keys($this->getSpecialModules()) as $target) {
                Redis::sSetPrefix($prefix);
                $this->assertClearModuleOnlyClearsTarget($target);

                // clearAll flushes the whole db, so restore the lookalike key for the next round
                Redis::sSetPrefix($lookalike);
                $this->cache->set($key, 'lookalike');
            }

            Redis::sSetPrefix($prefix);
            $this->cache->set($key, 'value');
            $this->cache->clearModule($key);
            $this->assertFalse($this->cache->check($key));

            Redis::sSetPrefix($lookalike);
            $this->assertTrue($this->cache->check($key), "Key with prefix '$lookalike' should be kept");
        } finally {
            Redis::sSetPrefix($originalPrefix);
            $this->cache->clearAll();
        }
    }

    public function clearModuleBoundaryProvider() {
        return array(
            'Foo' => array('Foo', array('Foo\Bar', 'FooBar')),
            'Foo\Bar' => array('Foo\Bar', array('Foo', 'Foo\Bar\Baz', 'FooBar', 'Foo\BarX')),
        );
    }

    /**
     * clearModule() must not reach modules that merely start with the same characters
     *
     * @dataProvider clearModuleBoundaryProvider
     *
     * @param string $target
     * @param string[] $others
     */
    public function testClearModuleDoesNotTouchSimilarModules($target, array $others) {
        $this->cache->clearAll();

        $targetKey = new CacheKey($target, 'prop');
        $this->cache->set($targetKey, 'value');

        $otherKeys = array();
        foreach ($others as $other) {
            $otherKeys[] = $otherKey = new CacheKey($other, 'prop');
            $this->cache->set($otherKey, 'value');
        }

        $this->cache->clearModule($targetKey);

        $this->assertFalse($this->cache->check($targetKey), "Module '$target' should be cleared");
        foreach ($otherKeys as $otherKey) {
            $this->assertTrue($this->cache->check($otherKey), "Module '{$otherKey->getModule()}' should be kept");
        }

        $this->cache->clearAll();
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