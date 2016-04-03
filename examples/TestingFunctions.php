<?php

use phpFastCache\CacheManager;

// Include composer autoloader
require '../src/autoload.php';

if(!isset($InstanceCache)) {
    $InstanceCache = CacheManager::getInstance();
}

function break_line() {
    echo "<br><hr><br>";
}

function output($string) {
    echo $string."\r\n<br>";
}
break_line();
    $key = "key1";
    output("Test Get");
    $value = $InstanceCache->get($key);
    output("{$key} = {$value}");
break_line();
    output("Write Data 123456");
    $InstanceCache->set($key,"123456",300);
break_line();
    output("Read Data Again");
    $value = $InstanceCache->get($key);
    output("{$key} = {$value}");
break_line();
    output("Delete Key");
    $InstanceCache->delete($key);
    $value = $InstanceCache->get($key);
    output("{$key} = {$value}");
break_line();
output("Write Data 100");
$InstanceCache->set($key,100,300);
output("Increase by 10");
$InstanceCache->increment($key,10);
$value = $InstanceCache->get($key);
output("{$key} = {$value}");
output("Decrease by 10");
$InstanceCache->decrement($key,10);
$value = $InstanceCache->get($key);
output("{$key} = {$value}");
break_line();
output("Finished Testing, Caching is Working Good");