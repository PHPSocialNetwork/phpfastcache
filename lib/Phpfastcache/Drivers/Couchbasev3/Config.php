<?php
/**
 *
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 *
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Couchbasev3;

use Phpfastcache\Drivers\Couchbase\Config as CoubaseV2Config;

class Config extends CoubaseV2Config
{
    /**
     * @var string
     */
    protected $bucketName = 'phpfastcache';

    protected $scopeName = self::DEFAULT_VALUE;

    protected $collectionName = self::DEFAULT_VALUE;

    /**
     * @return string
     */
    public function getScopeName(): string
    {
        return $this->scopeName;
    }

    /**
     * @param string $scopeName
     * @return Config
     */
    public function setScopeName(string $scopeName): Config
    {
        $this->scopeName = $scopeName;
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     */
    public function setCollectionName(string $collectionName): Config
    {
        $this->collectionName = $collectionName;
        return $this;
    }
}
