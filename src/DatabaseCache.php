<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

use Feather\Support\Database\Dbal;

/**
 * Description of DatabaseCache
 *
 * @author fcarbah
 */
class DatabaseCache implements ICache
{

    /** @var \Feather\Support\Database\Dbal * */
    protected $db;

    /** @var array * */
    protected $config;

    /** @var string * */
    protected $table = 'cache';

    /** @var string * */
    protected $data;

    /** @var string * */
    protected $lastUpdated;

    /** @var string * */
    protected $lastRead;

    /** @var \Feather\Cache\DatabaseCache * */
    private static $self;

    private function __construct($config)
    {

        try {

            $this->data = array();

            $this->config = $config;

            $this->init();
        } catch (\Exception $e) {
            throw new CacheException('Could not connect to database! ' . $e->getMessage(), 200);
        }
    }

    /**
     *
     * @param array $config
     * @return \Feather\Cache\DatabaseCache
     */
    public static function getInstance(array $config)
    {
        if (static::$self == null) {
            static::$self = new DatabaseCache($config);
        }

        return static::$self;
    }

    /**
     *
     * @return boolean
     */
    public function clear()
    {

        $this->connect();

        $sql = 'TRUNCATE ' . $this->table;

        $stmt = $this->db->prepare($sql);

        if ($stmt->execute()) {
            $this->data = array();
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {

        $this->connect();

        $sql = 'DELETE FROM ' . $this->table . ' WHERE cache_key = :key';

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);

        if ($stmt->execute()) {
            unset($this->data[$key]);
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

        if (isset($this->data[$key])) {
            return $this->data[$key]->data;
        }

        $object = $this->findKey($key);

        if (!$object) {
            return null;
        }

        if ($remove) {
            $this->delete($key);
        }

        if ($object->isExpired()) {
            $this->delete($key);
            return null;
        }

        $this->data[$key] = $object;

        return $object->data;
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

        $obj = new CacheObject($key, $value, (int) $expires);

        $expireAt = $expires == -1 ? 0 : $obj->expireAt;

        $sql = "INSERT INTO $this->table (cache_key, cache_data, expire_at) values(:key, :val, :expire_at)
                ON DUPLICATE KEY UPDATE
                cache_data = values(cache_data),
                expire_at = values(expire_at)
                ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->bindValue(':val', serialize($obj), \PDO::PARAM_STR);
        $stmt->bindValue(':expire_at', $expireAt, \PDO::PARAM_INT);

        if ($stmt->execute()) {
            $this->data[$key] = $obj;
            return true;
        }

        return false;
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
        if (isset($this->data[$key])) {
            $obj = $this->data[$key];
        } else {
            $obj = $this->findKey($key);
        }

        if (!$obj) {
            return false;
        }

        $expire = $expires !== null ? (int) $expires : $obj->expire;

        return $this->set($key, $value, $expire);
    }

    /**
     * connect to db
     */
    protected function connect()
    {
        if (!$this->db) {
            $options = $this->config['pdoOptions'] ?? [];
            $this->db = new Dbal($this->config['dsn'], $this->config['user'], $this->config['password'], $options);
            $this->db->connect();
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     *
     * @param string $key Cache key
     * @return \Feather\Cache\CacheObject|null
     */
    protected function findKey($key)
    {

        $this->connect();
        $sql = "SELECT cache_key, cache_data, expire_at FROM $this->table WHERE cache_key = :key";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':key', $key, \PDO::PARAM_STR);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row) {
            return unserialize($row['cache_data']);
        }

        return null;
    }

    /**
     * Loads cache
     */
    protected function init()
    {
        $this->table = $this->config['table'] ?? $this->table;
        $this->connect();
        $this->removeExpireData();
    }

    /**
     * Remove expire items from cache
     * @return int
     */
    protected function removeExpireData()
    {

        $sql = "DELETE FROM $this->table WHERE expire_at != 0 && expire_at <= :expireAt";

        $time = time();

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':expireAt', $time, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

}
