<?php
/**
 * This file is being included
 * directly with composer
 * therefore there is not
 * namespace available
 */
use Phpfastcache\core\InstanceManager;


/**
 * phpFastCache() Full alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
function phpFastCache($storage = 'auto', $config = array())
{
    trigger_error(__FUNCTION__ . '() is deprecated use instanceManager::getInstance() instead.', E_USER_DEPRECATED);
    return InstanceManager::getInstance($storage, $config);
}

/**
 * __c() Short alias
 * @param string $storage
 * @param array $config
 * @return mixed
 */
function __c($storage = 'auto', $config = array())
{
    trigger_error(__FUNCTION__ . '() is deprecated use instanceManager::getInstance() instead.', E_USER_DEPRECATED);
    return InstanceManager::getInstance($storage, $config);
}