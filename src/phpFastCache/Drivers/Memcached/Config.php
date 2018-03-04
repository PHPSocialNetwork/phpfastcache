<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace phpFastCache\Drivers\Memcached;

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
        'sasl_user' => false,
        'sasl_password' => false,
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
     * @return array
     */
    public function getServers(): array
    {
        return $this->servers;
    }

    /**
     * @param array $servers
     * @return Config
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
     * @return Config
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