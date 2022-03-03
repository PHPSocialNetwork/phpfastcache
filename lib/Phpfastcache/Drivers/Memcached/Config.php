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
     *           // If you use an UNIX socket set the host and port to null
     *           'host' => '127.0.0.1',
     *           //'path' => 'path/to/unix/socket',
     *           'port' => 11211,
     *           'saslUser' => null,
     *           'saslPassword' => null,
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
     * @var string
     */
    protected $optPrefix = '';

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
            if ($diff = array_diff(array_keys($server), ['host', 'port', 'saslUser', 'saslPassword', 'path'])) {
                throw new PhpfastcacheInvalidConfigurationException('Unknown keys for memcached server: ' . implode(', ', $diff));
            }

            if (!empty($server['host']) && !empty($server['path'])) {
                throw new PhpfastcacheInvalidConfigurationException('Host and path cannot be simultaneous defined.');
            }

            if ((isset($server['host']) && !is_string($server['host'])) || (empty($server['path']) && empty($server['host']))) {
                throw new PhpfastcacheInvalidConfigurationException('Host must be a valid string in "$server" configuration array if path is not defined');
            }

            if ((isset($server['path']) && !is_string($server['path'])) || (empty($server['host']) && empty($server['path']))) {
                throw new PhpfastcacheInvalidConfigurationException('Path must be a valid string in "$server" configuration array if host is not defined');
            }

            if (!empty($server['host']) && (empty($server['port']) || !is_int($server['port'])|| $server['port'] < 1)) {
                throw new PhpfastcacheInvalidConfigurationException('Port must be a valid integer in "$server" configuration array');
            }

            if (!empty($server['port']) && !empty($server['path'])) {
                throw new PhpfastcacheInvalidConfigurationException('Port should not be defined along with path');
            }

            if (!empty($server['saslUser']) && !empty($server['saslPassword']) && (!is_string($server['saslUser']) || !is_string($server['saslPassword']))) {
                throw new PhpfastcacheInvalidConfigurationException('If provided, saslUser and saslPassword must be a string');
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

    /**
     * @return string
     * @since 8.0.2
     */
    public function getOptPrefix(): string
    {
        return $this->optPrefix;
    }

    /**
     * @param string $optPrefix
     * @return Config
     * @since 8.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        $this->optPrefix = trim($optPrefix);
        return $this;
    }
}
