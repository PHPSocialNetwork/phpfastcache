<?php

	/*
	 * For Legacy Coding
	 * You can do require_once ("phpFastCache/phpFastCache.php");
	 * and call it right away
	 */

	// In your config files
	require_once("phpFastCache/phpFastCache.php");

	// now it's time to call the cache "anywhere" on your project

	$cache = phpFastCache();
	/* phpFastCache support more drivers
	$files_cache = phpFastCache("files");
	$memcache_cache = phpFastCache("memcache");
	*/

	/**
	 * Try to get $products from Caching First
	 * product_page is "identity keyword";
	 */
	$key = "product_page";
	$CachedString = $cache->get($key);

	if (is_null($CachedString)) {
		$CachedString = "Files Cache --> Cache Enabled --> Well done !";
		// Write products to Cache in 10 minutes with same keyword
		$InstanceCache->set($key, $CachedString, 600);

		echo "Files Cache --> Cached not enabled --> Reload page !";

	} else {
		echo $CachedString;
	}

	echo '<br /><br /><a href="/">Back to index</a>&nbsp;--&nbsp;<a href="/' . basename(__FILE__) . '">Reload</a>';
