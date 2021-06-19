<?php

namespace Feather\Cache;

/**
 * Description of CacheKey
 *
 * @author fcarbah
 */
class CacheKey
{

    /** @var string * */
    protected $key;

    /** @var int * */
    protected $expire;

    /** @var int * */
    protected $expireAt;

    /** @var string * */
    protected $filepath;

    /**
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     *
     * @return int
     */
    public function getExpire()
    {
        return $this->expire;
    }

    /**
     *
     * @return int
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     *
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     *
     * @return boolean
     */
    public function isExpired()
    {
        if ($this->expire == -1) {
            return false;
        }

        return time() > $this->expireAt;
    }

    public function setExpire($expire)
    {
        if ($expire === -1) {
            $this->expire = -1;
            $this->expireAt = null;
        } else {
            $this->expire = $expire;
            $this->expireAt = time() + $expire;
        }

        return $this;
    }

    public function setFilepath($filepath)
    {
        $this->filepath = $filepath;
        return $this;
    }

}
