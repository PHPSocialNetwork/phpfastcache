<?php

declare(strict_types=1);

/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Entities\ItemBatch;

chdir(__DIR__);
require_once __DIR__ . '/../../vendor/autoload.php';

$driverInstance = CacheManager::getInstance('Files', new ConfigurationOption([
    'preventCacheSlams' => true,
    'cacheSlamsTimeout' => 15,
]));

/**
 * Emulate an active ItemBatch
 */
$batchItem = $driverInstance->getItem('TestCacheSlamsProtection');
$batchItem->set(new ItemBatch($batchItem->getKey(), new \DateTimeImmutable()))->expiresAfter(3600);
$driverInstance->save($batchItem);

sleep(random_int(5, 15));

$batchItem->set(1337);
$driverInstance->save($batchItem);

exit(0);
