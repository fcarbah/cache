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

    public function get($key, $remove = false);

    public function set($key, $value, $expires = 300);

    public function clear();

    public function delete($key);

    public function update($key, $value, $expires = null);
}
