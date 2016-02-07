<?php
use Phpfastcache\core\Phpfastcache;
// Include composer autoloader
require '../vendor/autoload.php';

$cache = new Phpfastcache();
$info = $cache->systemInfo();

echo "<pre>";
var_dump($info);
echo "</pre>";
phpinfo();