<?php
use phpFastCache\CacheManager;

// Include composer autoloader
require_once("../../src/autoload.php");

$cache = CacheManager::getInstance();

// Write into cache
$cache->set("keyword", "data | array | object", 300);

// Read from Cache | return null or data
$data = $cache->get("keyword");
echo $data;

// Read object information | value | time from cache
$object = $cache->getInfo("keyword");
print_r($object);

// Delete from cache
$cache->delete("keyword");

// Clean up all cache
$cache->clean();

// Stats
$array = $cache->stats();

// Increase and Decrease Cache value - Return  true | false
$cache->increment("keyword", 1);
$cache->decrement("keyword", 1);

// Extend expiring time - Return true | false;
$cache->touch("keyword", 1000);

// Check Existing or not - Return true | false;
$cache->isExisting("keyword");

// Get & Set Multiple Items
// Same as above, but input is array();

$list = $cache->getMulti(array("key1", "key2", "key3"));

$list = $cache->getInfoMulti(array("key1", "key2", "key3"));

$cache->setMulti(array("key1", "value1", 300),
  array("key2", "value2", 600),
  array("key3", "value3", 1800));

$cache->deleteMulti(array("key1", "key2", "key3"));

$cache->isExistingMulti(array("key1", "key2", "key3"));

$cache->touchMulti(array(
  array("key", 300),
  array("key2", 400),
));

$cache->incrementMulti(array(
  array("key", 1),
  array("key2", 2),
));

$cache->decrementMulti(array(
  array("key", 1),
  array("key2", 2),
));









