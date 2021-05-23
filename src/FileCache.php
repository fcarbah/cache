<?php

namespace Feather\Cache;

use Feather\Cache\CacheObject;

/**
 * Description of FCache
 *
 * @author fcarbah
 */
class FileCache implements ICache
{

    protected $basePath;
    protected $keysFilename = 'feather_cache_keys';
    protected $keys = [];
    private static $self;

    private function __construct($cacheDir)
    {
        $this->validateCacheDirectory($cacheDir);
        $this->basePath = strripos($cacheDir, '/') === strlen($cacheDir) - 1 ? $cacheDir : $cacheDir . '/';
        $this->init();
    }

    /**
     *
     * @param type $cacheDir
     * @return \Feather\Cache\FileCache
     */
    public static function getInstance($cacheDir)
    {
        if (static::$self == null) {
            static::$self = new FileCache($cacheDir);
        }
        return static::$self;
    }

    /**
     * Empty cache including items cache forever
     * @return boolean
     */
    public function clear()
    {
        foreach ($this->keys as $cacheKey) {
            $this->removeFile($cacheKey->getFilePath());
        }
        $this->keys = [];
        $this->updateMetaData();
        return true;
    }

    /**
     * Deletes an item $key from cache
     * @param string $key
     * @return boolean
     */
    public function delete($key)
    {
        $fkey = $this->formatKey($key);
        $cacheKey = $this->keys[$fkey] ?? null;

        if ($cacheKey && $this->removeFile($cacheKey->getFilepath())) {
            unset($this->keys[$fkey]);
            $this->updateMetaData();
            return true;
        }

        return false;
    }

    /**
     * Add value to cache forever by $key until cache is cleard
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function forever($key, $value)
    {
        return $this->set($key, $value, -1);
    }

    /**
     * Retrieves the value cached by $key
     * @param string $key
     * @param boolean $remove
     * @return mixed
     */
    public function get($key, $remove = false)
    {
        $fkey = $this->formatKey($key);
        $val = null;
        try {
            $cacheKey = $this->keys[$fkey] ?? null;

            if ($cacheKey == null) {
                return null;
            }

            if ($cacheKey->isExpired()) {
                $remove = true;
            } else {
                $val = $this->loadValue($cacheKey->getFilepath());
            }

            if ($remove) {
                $this->delete($key);
            }

            return $val;
        } catch (\Exception $ex) {
            return $val;
        }
    }

    /**
     * Adds a value to cache based on $key
     * @param string $key
     * @param mixed $value
     * @param int $expires Number of seconds to keep data in cache
     * @return boolean
     */
    public function set($key, $value, $expires = 300)
    {

        if ($value === null) {
            return true;
        }

        $fkey = $this->formatKey($key);

        if (isset($this->keys[$fkey])) {
            return $this->update($key, $value);
        }

        $filepath = $this->getCacheFilepath($fkey);
        $cacheKey = new CacheKey($fkey);
        $cacheKey->setExpire($expires)
                ->setFilepath($filepath);

        if ($this->write($filepath, $value)) {
            $this->keys[$fkey] = $cacheKey;
            $this->updateMetaData();
            return true;
        }
        return false;
    }

    /**
     * Update value of existing cache key
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function update($key, $value)
    {

        if ($value === null) {
            return $this->delete($key);
        }

        $fkey = $this->formatKey($key);
        $filepath = $this->getCacheFilepath($fkey);

        if (!isset($this->keys[$fkey])) {
            return false;
        }

        $cacheKey = $this->keys[$fkey];
        $cacheKey->setExpire($cacheKey->getExpire());

        if ($this->write($filepath, $value)) {
            $this->keys[$fkey] = $cacheKey;
            $this->updateMetaData();
            return true;
        }

        return false;
    }

    /**
     * generates unique key based on key user supplied
     * @param string $key
     * @return string
     */
    protected function formatKey($key)
    {
        return md5($key);
    }

    /**
     * Get absolute path of cache file
     * @param string $fkey
     * @return string
     */
    protected function getCacheFilepath($fkey)
    {
        return $this->basePath . $fkey;
    }

    /**
     * Initializes cache and removes expired keys
     */
    protected function init()
    {
        $this->loadKeys();

        foreach ($this->keys as $cacheKey) {
            if ($cacheKey->isExpired() && $this->removeFile($cacheKey->getFilepath())) {
                unset($this->keys[$cacheKey->getKey()]);
            }
        }
    }

    /**
     * loads cache keys
     */
    protected function loadKeys()
    {
        $absPath = $this->basePath . $this->keysFilename;
        $file = fopen($absPath, 'a+');
        fclose($file);

        $str = file_get_contents($absPath);

        if ($str) {
            $keys = unserialize(base64_decode($str));
            $this->keys = is_array($keys) ? $keys : [];
        }
    }

    /**
     *
     * @param string $filepath
     * @return mixed
     */
    protected function loadValue($filepath)
    {

        if (!file_exists($filepath)) {
            return null;
        }

        $str = file_get_contents($filepath);

        if ($str) {
            return unserialize(base64_decode($str));
        }

        return null;
    }

    /**
     *
     * @param string $filepath
     * @return boolean
     */
    protected function removeFile($filepath)
    {
        if (file_exists($filepath)) {
            return $this->removeFile($filepath);
        }
        return false;
    }

    /**
     *
     */
    protected function updateMetaData()
    {
        $keys = base64_encode(serialize($this->keys));
        file_put_contents($this->basePath . $this->keysFilename, $keys);
    }

    /**
     *
     * @param string $cachePath
     * @throws CacheException
     */
    protected function validateCacheDirectory($cachePath)
    {
        if (!is_dir($cachePath)) {
            throw new CacheException($cachePath . ' is not a directory', 100);
        }

        if (!is_writable($cachePath)) {
            throw new CacheException($cachePath . ' is not a writeable directory', 101);
        }
    }

    /**
     *
     * @param string $filepath
     * @param mixed $data
     * @return int
     */
    protected function write($filepath, $data)
    {
        $value = base64_encode(serialize($data));
        return file_put_contents($filepath, $value);
    }

}
