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

namespace Phpfastcache\Drivers\Couchdb;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

class Config extends ConfigurationOption
{
    protected string $host = '127.0.0.1';
    protected int $port = 5984;
    protected string $username = '';
    protected string $password = '';
    protected bool $ssl = false;
    protected int $timeout = 10;
    protected string $database = Driver::COUCHDB_DEFAULT_DB_NAME;
/**
     * @return string
     */
    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @param string $database
     * @return Config
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function setDatabase(string $database): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
/** @see https://docs.couchdb.org/en/latest/api/database/common.html#put--db */
        if (\preg_match('#^[a-z][a-z0-9_\-+\$()/]+$#', $database)) {
            $this->database = $database;
            return $this;
        }

        throw new PhpfastcacheInvalidArgumentException(sprintf("Error: illegal_database_name Name: '%s'. 
            Only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed. Must begin with a letter.", $database));
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setHost(string $host): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->host = $host;
        return $this;
    }

    /**
     * @return int
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setPort(int $port): static
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
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setUsername(string $username): static
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
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setPassword(string $password): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->password = $password;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->ssl;
    }

    /**
     * @param bool $ssl
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setSsl(bool $ssl): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->ssl = $ssl;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return self
     * @throws PhpfastcacheLogicException
     */
    public function setTimeout(int $timeout): static
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->timeout = $timeout;
        return $this;
    }
}
