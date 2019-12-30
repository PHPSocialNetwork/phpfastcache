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
use Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException;


class Config extends ConfigurationOption
{
    /**
     * @var array
     *
     * Multiple server can be added this way:
     *       $cfg->setServers([
     *         [
     *           'host' => '127.0.0.1',
     *           'port' => 11211,
     *           'saslUser' => false,
     *           'saslPassword' => false,
     *         ]
     *      ]);
     */
    protected $servers = [];

    /**
     * @var string
     */
    protected $host = '127.0.0.1';

    /**
     * @var int
     */
    protected $port = 11211;

    /**
     * @var string
     */
    protected $saslUser = '';

    /**
     * @var string
     */
    protected $saslPassword = '';

    /**
     * @return string
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
     * @throws PhpfastcacheInvalidConfigurationException
     */
    public function setServers(array $servers): self
    {
        foreach ($servers as $server) {
            if ($diff = array_diff(['host', 'port', 'saslUser', 'saslPassword'], array_keys($server))) {
                throw new PhpfastcacheInvalidConfigurationException('Missing keys for memcached server: ' . implode(', ', $diff));
            }
            if ($diff = array_diff(array_keys($server), ['host', 'port', 'saslUser', 'saslPassword'])) {
                throw new PhpfastcacheInvalidConfigurationException('Unknown keys for memcached server: ' . implode(', ', $diff));
            }
            if (!is_string($server['host'])) {
                throw new PhpfastcacheInvalidConfigurationException('Host must be a valid string in "$server" configuration array');
            }
            if (!is_int($server['port'])) {
                throw new PhpfastcacheInvalidConfigurationException('Port must be a valid integer in "$server" configuration array');
            }
        }
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