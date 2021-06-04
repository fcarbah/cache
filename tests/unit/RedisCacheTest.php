<?php

use Feather\Cache\RedisCache;
use PHPUnit\Framework\TestCase;

/**
 * Description of RedisCacheTest
 *
 * @author fcarbah
 */
class RedisCacheTest extends TestCase
{

    /** @var \Feather\Cache\RedisCache * */
    protected static $cache;

    public static function setUpBeforeClass(): void
    {

        $server = 'localhost';
        $scheme = 'tcp';
        $port = 6379;
        $connOptions = [];

        static::$cache = RedisCache::getInstance($server, $port, $scheme, $connOptions);
        static::$cache->clear();
    }

    public static function tearDownAfterClass(): void
    {
        static::$cache = null;
    }

    /**
     * @test
     */
    public function addItemToCache()
    {
        $success = static::$cache->set('term1', 'Term 1');
        $this->assertTrue($success);
    }

    /**
     * @test
     */
    public function getAllKeysInCache()
    {
        static::$cache->set('my_key', 'My key');
        $keys = static::$cache->keys();
        $this->assertContains('term1', $keys);
        $this->assertEquals(2, count($keys));
    }

    /**
     * @test
     */
    public function getExistingKeyFromCache()
    {
        $term1 = static::$cache->get('term1');
        $this->assertTrue($term1 === 'Term 1');
    }

    /**
     * @test
     */
    public function shouldReturnNullIfKeyNotInCache()
    {
        $data = static::$cache->get('data');
        $this->assertTrue($data === null);
    }

    /**
     * @test
     */
    public function canUpdateExistingCacheKey()
    {
        $res = static::$cache->update('term1', 'Term 1 updated');
        $newVal = static::$cache->get('term1');
        $this->assertTrue($res);
        $this->assertEquals('Term 1 updated', $newVal);
    }

    /**
     * @test
     */
    public function willNotUpdateNonExistentKey()
    {
        $res = static::$cache->update('data', 'test data', 300);
        $this->assertFalse($res);
    }

    /**
     * @test
     */
    public function deleteExistingCacheItem()
    {
        $res = static::$cache->delete('term1');
        $term1 = static::$cache->get('term1');

        $this->assertTrue($res && $term1 === null);
    }

    /**
     * @test
     */
    public function willNotDeleteNonExistentKey()
    {
        $res = static::$cache->delete('term2');
        return $this->assertFalse($res);
    }

    /**
     * @test
     */
    public function willRemoveKeyAfterRetrieval()
    {
        static::$cache->set('key1', [1, 2, 3], 500);
        $key = static::$cache->get('key1', true);
        $key2 = static::$cache->get('key1');

        $this->assertTrue(is_array($key));
        $this->assertTrue($key2 === null);
        $this->assertContains(2, $key);
    }

    /**
     * @test
     */
    public function willReturnNullForAndRemoveExpiredKey()
    {
        static::$cache->set('temp', 'Expire after 5 seconds', 5);
        $beforeExpire = static::$cache->get('temp');
        sleep(6);
        $afterExpire = static::$cache->get('temp');
        $this->assertTrue($beforeExpire === 'Expire after 5 seconds');
        $this->assertTrue($afterExpire === null);
    }

    /**
     * @test
     */
    protected function willRemoveAllItemsFromCache()
    {
        static::$cache->set('alpa', ['a', 'b', 'c']);
        static::$cache->set('one', 1, -1);

        static::$cache->clear();

        $alpha = static::$cache->get('alpha');
        $one = static::$cache->get('one');

        $this->assertTrue($alpha === null);
        $this->assertTrue($one === null);
    }

}
