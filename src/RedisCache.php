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
class RedisCache implements Contracts\Cache {
    
    protected $scheme='tcp';
    protected $server;
    protected $port=3379;
    protected $client;
    private static $self;
    protected $keys=array();
    
    private function __construct($server,$port=6379,$scheme='tcp',array $connOptions=[]) {
        try{
            $this->scheme = $scheme;
            $this->server = $server;
            $this->port = $port;

            $this->client = new Client(array_merge([
                'host'=>$server,
                'port'=>$port,
                'scheme'=>$scheme
            ],$connOptions));
        }
        catch (\Exception $e){
            throw new CacheException('Error connecting to redis server', 300,$e);
        }
    }
    
    public static function getInstance($server,$port=6379,$scheme='tcp',array $connOptions=[]){
        if(self::$self ==null){
            self::$self = new RedisCache($server, $port, $scheme, $connOptions);
        }
        
        return self::$self;
    }
    
    public function clear() {
        if($this->client->del($this->keys)){
            $this->keys= array();
            return true;
        }
        
        return false;
    }

    public function delete($key) {
        if($this->client->del([$key])){
            $indx = array_search($key, $this->keys);
            unset($this->keys[$indx]);
            $this->keys= array_values($this->keys);
            return true;
        }
        return false;
    }

    public function get($key, $remove = false) {
        $data = $this->client->get($key);
        
        if(!$data){
            return null;
        }
        
        if($remove){
            $this->delete($key);
        }
        
        $obj = unserialize($data);
        
        return $obj->data;
    }

    public function set($key, $value, $expires = 300) {
        
        if(!$this->client->exists($key)){
            $object = new CacheObject($key, $value, $expires);
            $this->client->set($key,serialize($object));
            $this->client->expireAt($key,time()+$expires);
            $this->keys[] = $key;
            return true;
        }
        return $this->update($key, $value);
    }

    public function update($key, $value) {
        
        if($this->client->exists($key)){
            $this->delete($key);
            $this->set($key, $value);
            return true;
        }
        return false;
    }

}
