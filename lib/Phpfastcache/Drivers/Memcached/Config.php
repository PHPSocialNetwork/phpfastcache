<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Memcached;

use Phpfastcache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    /**
     * @var array
     */
    protected $servers = [
        [
            'host' => '127.0.0.1',
            'port' => 11211,
            'saslUser' => false,
            'saslPassword' => false,
        ],
    ];

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 11211;

    /**
     * @var bool
     */
    protected $saslUser = '';

    /**
     * @var bool
     */
    protected $saslPassword = '';

    /**
     * @return bool
     */
    public function getSaslUser(): string
    {
        return $this->saslUser;
    }

    /**
     * @param string $saslUser
     * @return self
     */
    public function setSaslUser(string $saslUser): self
    {
        $this->saslUser = $saslUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getSaslPassword(): string
    {
        return $this->saslPassword;
    }

    /**
     * @param string $saslPassword
     * @return self
     */
    public function setSaslPassword(string $saslPassword): self
    {
        $this->saslPassword = $saslPassword;
        return $this;
    }

    /**
     * @return array
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param array $servers
     * @return self
     */
    public function setServers(array $servers): self
    {
        $this->servers = $servers;
        return $this;
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
     */
    public function setHost(string $host): self
    {
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
     * @return Config
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }
}