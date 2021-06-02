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

    /** @var string * */
    protected $key;

    /** @var int * */
    protected $addTime;

    /** @var int * */
    protected $expireAt;

    /** @var mixed * */
    protected $data;

    /** @var int * */
    protected $expire;

    /**
     *
     * @param string $key
     * @param mixed $data
     * @param int $expire Expire time in seconds
     */
    public function __construct($key, $data, $expire)
    {
        $this->key = $key;
        $this->data = $data;
        $this->addTime = time();
        $this->expire = intval($expire);
        $this->expireAt = time() + intval($expire);
    }

    public function isExpired()
    {
        return $this->expire !== -1 && time() > $this->expireAt;
    }

    public function __get($name)
    {
        if (isset($this->{$name})) {
            return $this->{$name};
        }
        return null;
    }

}
