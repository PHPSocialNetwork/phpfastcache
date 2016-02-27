<?php
use phpFastCache\Core\phpFastCache;
// Include composer autoloader
require_once("../../src/autoload.php");

$cache = new phpFastCache();
$info = $cache->systemInfo();

echo "<pre>";
var_dump($info);
echo "</pre>";
phpinfo();