<?php

// Setup your cronjob to run this file every 30 mins - 60 mins to help clean up the expired files faster

require_once (dirname(__FILE__)."/../phpFastCache.php");

use phpFastCache\CacheManager;

$setup = array(
    "path"  => "/your_path/to_clean/"
);

$cache = CacheManager::Files($setup);

// clean up expired files cache every hour
$cache->auto_clean_expired(3600*1);