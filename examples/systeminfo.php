<?php

include("phpfastcache.php");

$cache = new phpFastCache();
$info = $cache->systemInfo();

echo "<pre>";
print_r($info);
echo "</pre>";
phpinfo();