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
class DatabaseCache implements Contracts\Cache {
    protected $db;
    protected $config;
    protected $table='feather_cache';
    protected $data;
    protected $lastUpdated;
    protected $lastRead;
    
    public function __construct($config) {
        try{
            $this->data = array();
            $this->config = $config;
            $this->connect();
        }
        catch (\Exception $e){
            throw new CacheException('Could not connect to database! '.$e->getMessage(),200);
        }
    }


    public function clear() {
        
        $this->connect();
        
        $sql = 'delete from '.$this->table;
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute();
        
        return $this;
    }

    public function delete($key) {
        
        $this->connect();
        
        $sql = 'delete from '.$this->table .' where key=:ckey';
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue('ckey', $key);
        
        $stmt->execute();
        
        return $this;
    }

    public function get($key, $remove = false) {
        
        $this->load();
        
        if(!$this->data[$key]){
            return null;
        }
        
        if($remove){
            $this->delete($key);
        }
        
        $object =  unserialize($this->data[$key]->value);
        
        if($object->isExpired()){
            $this->delete($key);
            return null;
        }
        
        return $object->data;
        
    }

    public function set($key, $value, $expires = 300) {
        
        $this->load();
        
        if($this->data[$key]){
            return $this->update($key, $value);
        }
        
        $obj = new CacheObject($key, $value, $expires);
        
        $sql = 'insert into '.$this->table.' (key,value) values(:key,:val)';
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue('key', $key);
        $stmt->bindValue('value', serialize($obj));
        
        $stmt->execute();
        
        $this->lastUpdated = time();
        
        return true;
    }

    public function update($key, $value) {
        
        $this->load();
        
        $data = $this->data[$key];
        
        if(!$data){
            return this;
        }
        
        $sql = 'update '.$this->table. 'set value=:val where key=:ckey';
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->bindValue('key', $key);
        $stmt->bindValue('value', serialize($value));
        
        $stmt->execute();
        
        $this->lastUpdated = time();
        
        return $this;
    }
    
    protected function load(){
        
        if($this->lastUpdated != null && $this->lastRead != null && $this->lastUpdated < $this->lastRead){
            return;
        }
        
        $this->connect();
        
        $sql = 'select * from '.$this->table;
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute();
        
        foreach($stmt->fetchAll() as $row){
            $this->data[$row['key']] = $row;
        }
        
        $this->lastRead = time();
    }
    
    protected function connect(){
        if(!$this->db){
            $this->db= new \PDO($this->config['dsn'], $this->config['user'], $this->config['password']);
        }
    }
    
    

}
