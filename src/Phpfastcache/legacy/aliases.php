<?php
/**
 * This file ensure
 * a maximum compatibility
 * for user that do not
 * make use of composer
 */
use Phpfastcache\core\InstanceManager;

if(!defined('PHPFASTCACHE_LOADED_VIA_COMPOSER'))
{
    require_once 'required_files.php';
}

/**
 * phpFastCache() Full alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
if(!function_exists("phpFastCache")) {
	function phpFastCache($storage = 'auto', $config = array())
	{
		return InstanceManager::getInstance($storage, $config);
	}
}


/**
 * __c() Short alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
if(!function_exists("__c")) {
	function __c ( $storage = 'auto' , $config = array() ) {
		return InstanceManager::getInstance ( $storage , $config );
	}
}