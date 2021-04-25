<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

use Feather\Cache\CacheObject;

/**
 * Description of Cache
 *
 * @author fcarbah
 */
class FileCache implements ICache
{

    protected $cachePath;
    protected $filePath;
    protected $keysPath;
    protected $keys;
    protected $file;
    protected $lastUpdated;
    protected $lastRead;
    protected $cacheLoaded = false;
    private static $self;

    /**
     *
     * @param string $cachePath
     * @throws CacheException
     */
    private function __construct($cachePath)
    {

        $this->keys = array();
        $this->file = array();

        $this->cachePath = strripos($cachePath, '/') === strlen($cachePath) - 1 ? $cachePath : $cachePath . '/';

        $this->filePath = $this->cachePath . 'feather_cache';
        $this->keysPath = $this->cachePath . 'feather_cache_keys';

        if (!is_dir($this->cachePath)) {
            throw new CacheException($cachePath . ' is not a directory', 100);
        }

        if (!is_writable($this->cachePath)) {
            throw new CacheException($cachePath . ' is not a writeable directory', 101);
        }

        $this->init();

        //$this->readFile();
    }

    /**
     *
     * @param string $cachePath
     * @return \Feather\Cache\ICache
     */
    public static function getInstance($cachePath)
    {
        if (static::$self == null) {
            static::$self = new FileCache($cachePath);
        }

        return static::$self;
    }

    /**
     *
     * @return boolean
     */
    public function clear()
    {
        $this->keys = array();
        $this->file = array();
        $this->write();
        return true;
    }

    /**
     *
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {

        $index = array_search($key, $this->keys);

        if ($index === false) {
            return false;
        }

        $this->readFile();

        unset($this->keys[$index]);
        unset($this->file[$index]);

        $this->keys = array_values($this->keys);
        $this->file = array_values($this->file);

        $this->write();
        $this->closeFile();
        return true;
    }

    /**
     *
     * @param string $key
     * @param boolean $remove
     * @return mixed
     */
    public function get($key, $remove = false)
    {

        $index = array_search($key, $this->keys);

        if ($index === false) {
            return null;
        }

        $this->readFile();

        $obj = unserialize($this->file[$index]);

        if ($obj->isExpired()) {
            $this->delete($key);
            return null;
        }

        if ($remove) {
            $this->delete($key);
        }

        $this->closeFile();

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

        $index = array_search($key, $this->keys);

        if ($index === false) {
            $obj = new CacheObject($key, $value, $expires);
            $this->file[] = serialize($obj);
            $this->keys[] = $key;
            $this->write();
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

        $index = array_search($key, $this->keys);

        $object = unserialize($this->file[$index]);

        $updateObj = new CacheObject($key, $value, $object->expire);

        $this->file[$index] = serialize($updateObj);

        $this->write();

        return true;
    }

    /**
     *
     */
    protected function init()
    {

        $f = fopen($this->filePath, 'a+');
        fclose($f);

        $kf = fopen($this->keysPath, 'a+');
        fclose($kf);

        $this->keys = file($this->keysPath);

        if (!$this->keys) {
            $this->keys = array();
        }
    }

    /**
     * Read cache data from file
     */
    protected function readFile()
    {
        if (!$this->cacheLoaded) {
            $this->file = file($this->filePath);
            if (!$this->file) {
                $this->file = array();
            } else {
                $this->cacheLoaded = true;
            }
        }
    }

    public function closeFile()
    {
        $this->file = [];
        $this->cacheLoaded = false;
    }

    /**
     *
     * @return boolean
     * @throws CacheException
     */
    protected function write()
    {
        try {
            $string = implode(PHP_EOL, $this->file);
            file_put_contents($this->filePath, $string);

            $keyStr = implode(PHP_EOL, $this->keys);
            file_put_contents($this->keysPath, $keyStr);
            $this->closeFile();
            return true;
        } catch (\Exception $e) {
            $this->closeFile();
            throw new CacheException($e->getMessage(), 105);
        }
    }

}
