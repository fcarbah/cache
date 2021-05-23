<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

/**
 * Description of DatabaseCache
 *
 * @author fcarbah
 */
class DatabaseCache implements ICache
{

    protected $db;
    protected $config;
    protected $table = 'feather_cache';
    protected $data;
    protected $lastUpdated;
    protected $lastRead;
    private static $self;

    private function __construct($config)
    {

        try {
            $this->data = array();
            $this->config = $config;
            $this->connect();
            $this->load();
        } catch (\Exception $e) {
            throw new CacheException('Could not connect to database! ' . $e->getMessage(), 200);
        }
    }

    public static function getInstance($config)
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

        $sql = 'truncate ' . $this->table;

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

        $sql = 'delete from ' . $this->table . ' where cache_key=:ckey';

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':ckey', $key);

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

        if (!isset($this->data[$key])) {
            return null;
        }

        $object = $this->data[$key];

        if ($remove) {
            $this->delete($key);
        }

        if ($object->isExpired()) {
            $this->delete($key);
            return null;
        }

        return $object->filepath;
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

        if (isset($this->data[$key])) {
            return $this->update($key, $value);
        }

        $obj = new CacheObject($key, $value, $expires);

        $sql = 'insert into ' . $this->table . ' (cache_key,value) values(:key,:val)';

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':val', serialize($obj));

        if ($stmt->execute()) {
            $this->setData($key, $obj);
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function update($key, $value)
    {

        $data = isset($this->data[$key]) ? $this->data[$key] : null;

        if (!$data) {
            return false;
        }

        $sql = 'update ' . $this->table . ' set value=:val where cache_key=:key';

        $obj = $data;

        $updObj = new CacheObject($key, $value, $obj->expire);

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':val', serialize($updObj));

        if ($stmt->execute()) {
            $this->setData($key, $updObj);
            return true;
        }

        return false;
    }

    /**
     * connect to db
     */
    protected function connect()
    {
        if (!$this->db) {
            $this->db = new \PDO($this->config['dsn'], $this->config['user'], $this->config['password']);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
     * Loads cache
     */
    protected function load()
    {

        $this->connect();

        $sql = 'select * from ' . $this->table;

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            $this->data[$row['cache_key']] = unserialize($row['value']);
        }
    }

    /**
     *
     * @param string $key
     * @param mixed $object
     */
    protected function setData($key, $object)
    {
        $this->data[$key] = $object;
    }

}
