<?php

/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author  Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Cluster;

use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;

interface ClusterPoolInterface extends ExtendedCacheItemPoolInterface
{
    /**
     * @return AggregatablePoolInterface[]
     */
    public function getClusterPools(): array;

    /**
     * @return ConfigurationOptionInterface[]
     */
    public function getConfigs(): array;
}
