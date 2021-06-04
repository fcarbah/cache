<?php

namespace Feather\Cache;

use Predis\Client;

/**
 * Description of RedisCache
 *
 * @author fcarbah
 */
class RedisCache implements ICache
{

    /** @var string * */
    protected $scheme = 'tcp';

    /** @var string * */
    protected $server;

    /** @var int * */
    protected $port = 3379;

    /** @var \Predis\Client * */
    protected $client;

    /** @var \Feather\Cache\RedisCache * */
    private static $self;

    /** @var array * */
    protected $keys = array();

    /** @var string * */
    protected $namespace = "__feather";

    /**
     *
     * @param string $server
     * @param int $port
     * @param string $scheme
     * @param array $connOptions
     * @throws CacheException
     */
    private function __construct($server, $port = 6379, $scheme = 'tcp', array $connOptions = [])
    {
        try {
            $this->scheme = $scheme;
            $this->server = $server;
            $this->port = $port;

            $this->client = new Client(array_merge([
                        'host' => $server,
                        'port' => $port,
                        'scheme' => $scheme
                            ], $connOptions));
        } catch (\Exception $e) {
            throw new CacheException('Error connecting to redis server', 300, $e);
        }
    }

    /**
     *
     * @param string $server
     * @param imt $port
     * @param string $scheme
     * @param array $connOptions
     * @return type
     */
    public static function getInstance($server, $port = 6379, $scheme = 'tcp', array $connOptions = [])
    {
        if (static::$self == null) {
            static::$self = new RedisCache($server, $port, $scheme, $connOptions);
        }

        return static::$self;
    }

    /**
     *
     * @return boolean
     */
    public function clear()
    {
        $pattern = $this->namespace . '*';
        if ($this->client->del($pattern)) {
            $this->keys = array();
            return true;
        }

        return false;
    }

    /**
     *
     * @param type $key
     * @return boolean
     */
    public function delete($key)
    {
        $fkey = $this->formatKey($key);

        if ($this->client->del([$fkey])) {
            $indx = array_search($key, $this->keys);
            unset($this->keys[$indx]);
            $this->keys = array_values($this->keys);
            return true;
        }
        return false;
    }

    /**
     *
     * @param string $key
     * @param boolean $remove
     * @return mixed
     */
    public function get($key, $remove = false)
    {
        $data = $this->client->get($this->formatKey($key));

        if (!$data) {
            return null;
        }

        if ($remove) {
            $this->delete($key);
        }

        $obj = unserialize($data);

        if ($obj->isExpired()) {
            $this->delete($key);
            return null;
        }

        return $obj->data;
    }

    /**
     * Get all the keys in cache
     * @return array
     */
    public function keys(): array
    {
        $pattern = $this->namespace . '*';
        $namespace = $this->namespace;

        $keys = $this->client->keys($pattern);

        return array_map(function($item) use($namespace) {
            return str_replace($namespace, '', $item);
        }, $keys);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @param int $expires
     * @return boolean
     */
    public function set($key, $value, $expires = 300)
    {
        $fkey = $this->formatKey($key);

        if (!$this->client->exists($fkey)) {
            $object = new CacheObject($key, $value, (int) $expires);
            $this->client->set($fkey, serialize($object));
            if ($expires === -1) {
                $this->client->persist($fkey);
            }
            $this->client->expireAt($fkey, time() + (int) $expires);
            $this->keys[] = $key;
            return true;
        }
        return $this->update($key, $value);
    }

    /**
     *
     * @param string $namespace
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @param int $expires
     * @return boolean
     */
    public function update($key, $value, $expires = null)
    {

        $fkey = $this->formatKey($key);

        if ($this->client->exists($fkey)) {
            $cacheObject = $this->client->get($fkey);
            if ($expires === null && $cacheObject) {
                $cacheObject = unserialize($cacheObject);
                $expires = $cacheObject->expire;
            }

            $this->delete($key);
            $this->set($key, $value, $expires);
            return true;
        }
        return false;
    }

    protected function formatKey($key)
    {
        return $this->namespace . $key;
    }

}
