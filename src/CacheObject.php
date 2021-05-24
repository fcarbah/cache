<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

/**
 * Description of CacheObject
 *
 * @author fcarbah
 */
class CacheObject
{

    protected $key;
    protected $addTime;
    protected $expireTime;
    protected $data;
    protected $expire;

    public function __construct($key, $data, $expireTime)
    {
        $this->key = $key;
        $this->data = $data;
        $this->addTime = time();
        $this->expire = intval($expireTime);
        $this->expireTime = time() + intval($expireTime);
    }

    public function isExpired()
    {
        return $this->expire !== -1 && time() > $this->expireTime;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

}
