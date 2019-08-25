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
    
    protected $cachePath;
    protected $filePath;
    protected $keysPath;
    protected $keys;
    protected $file;
    protected $lastUpdated;
    protected $lastRead;
    private static $self;
    
    private function __construct($cachePath){

        $this->keys=array();
        $this->file = array();

        $this->cachePath = strripos($cachePath,'/') === strlen($cachePath)-1? $cachePath : $cachePath.'/';

        $this->filePath = $this->cachePath.'feather_cache';
        $this->keysPath = $this->cachePath.'feather_cache_keys';

        if(!is_dir($this->cachePath)){
            throw new CacheException($cachePath.' is not a directory', 100);
        }

        if(!is_writable($this->cachePath)){
            throw new CacheException($cachePath.' is not a writeable directory', 101);
        }

        $this->init();

        $this->readFile();
        
    }
    
    public static function getInstance($cachePath){
        if(self::$self == null){
            self::$self = new FileCache($cachePath);
        }
        
        return self::$self;
    }

        public function clear() {
        $this->keys = array();
        $this->file = array();
        $this->write();
        return true;
    }
    
    public function delete($key) {
        
        $index = array_search($key,$this->keys);
        
        if($index ===false){
            return false;
        }
        
        unset($this->keys[$index]);
        unset($this->file[$index]);
        
        $this->keys = array_values($this->keys);
        $this->file = array_values($this->file);
        
        $this->write();

        return true;
    }

    public function get($key, $remove = false) {
        
        $index = array_search($key,$this->keys);
        
        if($index ===false){
            return null;
        }

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
            $obj = new CacheObject($key, $value, $expires);
            $this->file[] = serialize($obj);
            $this->keys[] = $key;
            $this->write();
            return true;
        }
        
        return $this->update($key, $value);
    }

    public function update($key, $value) {

        $index = array_search($key,$this->keys);
        
        $object = unserialize($this->file[$index]);
        
        $updateObj = new CacheObject($key, $value, $object->expire);
        
        $this->file[$index] = serialize($updateObj);
        
        $this->write();
        
        return true;

    }
    
    protected function init(){
        
        $f = fopen($this->filePath, 'w+');
        fclose($f);
        
        $kf = fopen($this->keysPath, 'w+');
        fclose($kf);
        
        $this->keys = file($this->keysPath);
        
        if(!$this->keys){
            $this->keys = array();
        }

    }
    
    protected function readFile(){

        $this->file = file($this->filePath);
        if(!$this->file){
           $this->file = array(); 
        }

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
