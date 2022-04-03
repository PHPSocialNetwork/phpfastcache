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

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected const DEFAULT_VALUE = '_default';
    protected string $host = '127.0.0.1';
    protected int $port = 8091;
// SSL: 18091

    protected string $username = '';
    protected string $password = '';
    protected string $bucketName = self::DEFAULT_VALUE;
    protected string $scopeName = self::DEFAULT_VALUE;
    protected string $collectionName = self::DEFAULT_VALUE;
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getBucketName(): string
    {
        return $this->bucketName;
    }

    /**
     * @param string $bucketName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setBucketName(string $bucketName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->bucketName = $bucketName;
        return $this;
    }
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
     * @throws PhpfastcacheLogicException
     */
    public function setScopeName(string $scopeName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
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
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collectionName = $collectionName;
        return $this;
    }
}
