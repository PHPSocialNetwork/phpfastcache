<?php

/**
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Entities\ItemBatch;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';

$driverInstance = CacheManager::getInstance('Files', new ConfigurationOption([
  'useStaticItemCaching' => false,
]));

/**
 * Emulate an active ItemBatch
 */
$batchItem = $driverInstance->getItem('TestUseStaticItemCaching');
$batchItem->set('abcdef-123456');
$driverInstance->save($batchItem);

exit(0);