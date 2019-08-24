<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Feather\Cache;
use Feather\Cache\Contracts\Cache;
use Feather\Cache\CacheObject;
/**
 * Description of Cache
 *
 * @author fcarbah
 */
class FileCache implements Cache {
    
    protected $sessionPath;
    protected $filePath;
    protected $keysPath;
    protected $keys;
    protected $file;
    protected $lastUpdated;
    protected $lastRead;
    private static $self;
    
    public function __construt($sessionPath){
        
        if(self::$self == null){
            
            $this->keys=array();
            $this->file = array();
            
            $this->sessionPath = strripos($sessionPath,'/') === strlen($sessionPath)-1? $sessionPath : $sessionPath.'/';

            $this->filePath = $this->sessionPath.'feather_cache';
            $this->keysPath = $this->sessionPath.'feather_cache_keys';

            if(!is_dir($this->sessionPath)){
                throw new CacheException($sessionPath.' is not a directory', 100);
            }

            if(!is_writable($this->filePath)){
                throw new CacheException($sessionPath.' is not a writeable directory', 101);
            }
            
            self::$self = $this;
        }
        
        return self::$self;
        
    }
    
    public function clear() {
        
    }
    
    public function delete($key) {
        
        $index = array_search($key,$this->keys);
        
        if($index ===false){
            return this;
        }
        
        unset($this->keys[$index]);
        unset($this->file[$index]);
        
        $this->keys = array_values($this->keys);
        $this->file = array_values($this->file);
        
        return $this;
    }

    public function get($key, $remove = false) {
        
        $index = array_search($key,$this->keys);
        
        if($index ===false){
            return null;
        }
        
        $this->readFile();
        
        $obj = unserialize($this->file[$index]);
        
        if($obj->isExpired()){
            $this->delete($key);
            return null;
        }
        
        if($remove){
            $this->delete($key);
        }
        
        return $obj->data;
        
    }

    public function set($key, $value, $expires = 300) {
        
        $index = array_search($key,$this->keys);
        
        if($index===false){
            $this->readFile();
            $obj = new CacheObject($key, $value, $expires);
            $this->file[] = serialize($obj);
            $this->keys[] = $key;
            $this->write();
            return $this;
        }
        
        return $this->update($key, $value);
    }

    public function update($key, $value) {
        
        $this->readFile();
        
        $index = array_search($key,$this->keys);
        
        $object = unserialize($this->file[$index]);
        
        $updateObj = new CacheObject($key, $value, $object->expire);
        
        $this->file[$index] = serialize($updateObj);
        
        $this->write();
        
        return $this;

    }
    
    protected function init(){
        
        $f = fopen($this->filePath, 'r+');
        fclose($f);
        
        $kf = fopen(fopen($this->keysPath, 'r+'));
        fclose($kf);
        
        $this->keys = file($this->keysPath);
        
        $this->lastUpdated = time();
        
    }
    
    protected function readFile(){
        
        if($this->lastUpdated != null && $this->lastRead != null && $this->lastUpdated < $this->lastRead){
            return;
        }
        
        $this->file = file($this->filePath);
        $this->lastRead = time();
    }
    
    protected function write(){
        try{
            $string =implode(PHP_EOL,$this->file);
            file_put_contents($this->filePath, $string);

            $keyStr = implode(PHP_EOL,$this->keys);
            file_put_contents($this->keysPath, $keyStr);

            return true;
        }
        catch(\Exception $e){
            throw new CacheException($e->getMessage(), 105);
        }
    }


}
