<?php

//run composer install prior to running example

require '../vendor/autoload.php';

use Feather\Cache\FileCache;

$cache = new FileCache(dirname(__FILE__));

$cache->set('temp','tuesday');

$cache->set('temp2','west');

var_dump('temp should be tuesday: '.$cache->get('temp'));

var_dump('temp2 should be west: '.$cache->get('temp2',true));

$cache->update('temp', 'wednesday');

var_dump('temp was updated to wednesday: '.$cache->get('temp'));

var_dump('temp3 is not set and should be null: ',$cache->get('temp3'));

var_dump('temp2 was removed and should be is null: ',$cache->get('temp2'));

$cache->clear();

var_dump('cache was clear and therefore temp should be null',$cache->get('temp'));

