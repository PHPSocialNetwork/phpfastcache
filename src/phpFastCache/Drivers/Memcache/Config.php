<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace phpFastCache\Drivers\Memcache;

use phpFastCache\Config\ConfigurationOption;

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
    protected $saslUser = false;

    /**
     * @var bool
     */
    protected $saslPassword = false;

    /**
     * @return bool
     */
    public function isSaslUser(): bool
    {
        return $this->saslUser;
    }

    /**
     * @param bool $saslUser
     * @return self
     */
    public function setSaslUser(bool $saslUser): self
    {
        $this->saslUser = $saslUser;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSaslPassword(): bool
    {
        return $this->saslPassword;
    }

    /**
     * @param bool $saslPassword
     * @return self
     */
    public function setSaslPassword(bool $saslPassword): self
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
     * @return self
     */
    public function setPort(int $port): self
    {
        $this->port = $port;
        return $this;
    }
}