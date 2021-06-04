<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Feather\Cache;

/**
 *
 * @author fcarbah
 */
interface ICache
{

    /**
     * Retrieves data associated with key from cache
     * @param string $key
     * @param bool $remove
     * @return mixed
     */
    public function get($key, $remove = false);

    /**
     * Adds key and data to cache
     * @param string $key
     * @param mixed $value
     * @param int $expires
     * @return boolean
     */
    public function set($key, $value, $expires = 300);

    /**
     * Removes all items from cache
     * @return boolean
     */
    public function clear();

    /**
     * Remove key from cache
     * @param int $key
     * @return boolean
     */
    public function delete($key);

    /**
     * Update existing cache key data
     * @param string $key
     * @param mixed $value
     * @param int $expires
     * @return boolean
     */
    public function update($key, $value, $expires = null);

    /**
     *  Get list of all keys in the cache
     * @return array
     */
    public function keys();
}
