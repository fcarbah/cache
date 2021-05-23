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

    public function clear()
    {
        foreach ($this->keys as $cacheKey) {
            unlink($cacheKey->getFilePath());
        }
        $this->keys = [];
        $this->updateMetaData();
        return true;
    }

    public function delete($key)
    {
        $fkey = $this->formatKey($key);
        $cacheKey = $this->keys[$fkey] ?? null;

        if ($cacheKey && unlink($cacheKey->getFilepath())) {
            unset($this->keys[$fkey]);
            $this->updateMetaData();
            return true;
        }

        return false;
    }

    public function forever($key, $value)
    {
        return $this->set($key, $value, -1);
    }

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
     *
     * @param string $key
     * @param mixed $value
     * @param int $expires
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
     *
     * @param string $key
     * @return string
     */
    protected function formatKey($key)
    {
        return md5($key);
    }

    protected function getCacheFilepath($fkey)
    {
        return $this->basePath . $fkey;
    }

    protected function init()
    {
        $this->loadKeys();

        foreach ($this->keys as $cacheKey) {
            if ($cacheKey->isExpired() && unlink($cacheKey->getFilepath())) {
                unset($this->keys[$cacheKey->getKey()]);
            }
        }
    }

    protected function loadKeys()
    {
        $absPath = $this->basePath . $this->keysFilename;
        $file = fopen($absPath, 'a+');
        fclose($file);

        $str = file_get_contents($absPath);

        if ($str) {
            $this->keys = unserialize(base64_decode($str));
        }
    }

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

    protected function updateMetaData()
    {
        $keys = base64_encode(serialize($this->keys));
        file_put_contents($this->basePath . $this->keysFilename, $keys);
    }

    protected function validateCacheDirectory($cachePath)
    {
        if (!is_dir($cachePath)) {
            throw new CacheException($cachePath . ' is not a directory', 100);
        }

        if (!is_writable($cachePath)) {
            throw new CacheException($cachePath . ' is not a writeable directory', 101);
        }
    }

    protected function write($filepath, $data)
    {
        $value = base64_encode(serialize($data));
        return file_put_contents($filepath, $value);
    }

}
