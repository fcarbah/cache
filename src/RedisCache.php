<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

use Predis\Client;

/**
 * Description of RedisCache
 *
 * @author fcarbah
 */
class RedisCache implements ICache
{

    protected $scheme = 'tcp';
    protected $server;
    protected $port = 3379;
    protected $client;
    private static $self;
    protected $keys = array();

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
        if ($this->client->del($this->keys)) {
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
        if ($this->client->del([$key])) {
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
     * @return type
     */
    public function get($key, $remove = false)
    {
        $data = $this->client->get($key);

        if (!$data) {
            return null;
        }

        if ($remove) {
            $this->delete($key);
        }

        $obj = unserialize($data);

        return $obj->data;
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

        if (!$this->client->exists($key)) {
            $object = new CacheObject($key, $value, $expires);
            $this->client->set($key, serialize($object));
            $this->client->expireAt($key, time() + $expires);
            $this->keys[] = $key;
            return true;
        }
        return $this->update($key, $value);
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function update($key, $value)
    {

        if ($this->client->exists($key)) {
            $this->delete($key);
            $this->set($key, $value);
            return true;
        }
        return false;
    }

}
