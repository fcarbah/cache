<?php

//run composer install prior to running example

require '../vendor/autoload.php';

use Feather\Cache\FileCache;
use Feather\Cache\DatabaseCache;

function testFileCache(){

    $cache = FileCache::getInstance(dirname(__FILE__));

    $cache->set('temp','tuesday');

    $cache->set('temp2','west');

    var_dump('temp should be tuesday: '.$cache->get('temp'));

    var_dump('temp2 should be west: '.$cache->get('temp2',true));

    $cache->update('temp', 'wednesday');
    
    $cache2 = FileCache::getInstance('');

    var_dump('temp was updated to wednesday: '.$cache2->get('temp'));

    var_dump('temp3 is not set and should be null: ',$cache->get('temp3'));

    var_dump('temp2 was removed and should be null: ',$cache->get('temp2'));

    $cache->clear();

    var_dump('cache was clear and therefore temp should be null',$cache->get('temp'));
}


function testDbCache(){
    $dbconfig=[
        'dsn'=>'mysql:host=localhost;dbname=test',
        'user'=>'root',
        'password'=>''
    ];

    $dbCache = DatabaseCache::getInstance($dbconfig);

    $dbCache->set('dbtemp','one');
    $dbCache->set('dbtemp2','two');

    var_dump('dbtemp should be one: '.$dbCache->get('dbtemp'));

    var_dump('dbtemp2 should be two: '.$dbCache->get('dbtemp2',true));

    $dbCache->update('dbtemp', 'ten');

    var_dump('dbtemp3 should be null .was not set: ',$dbCache->get('dbtemp3'));

    var_dump('dbtemp was updated to ten: '.$dbCache->get('dbtemp'));

    var_dump('dbtemp2 was removed and should be null: ',$dbCache->get('dbtemp2'));

    $dbCache->clear();

    var_dump('dbtemp 2 should be null. cache was cleared: ',$dbCache->get('dbtemp'));
}

testFileCache();

testDbCache();


