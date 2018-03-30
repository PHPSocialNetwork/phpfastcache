<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace Phpfastcache\Drivers\Couchbase;

use Phpfastcache\Config\ConfigurationOption;

class Config extends ConfigurationOption
{
    /**
     * @var string
     */
    protected $host = '127.0.0.1';
    /**
     * @var int
     */
    protected $port = 8091;
    /**
     * @var string
     */
    protected $username = '';
    /**
     * @var string
     */
    protected $password = '';
    /**
     * @var array
     */
    protected $buckets = [
      [
        'bucket' => 'default',
        'password' => '',
      ],
    ];

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
    public function setHost(string $host): Config
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
    public function setPort(int $port): Config
    {
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
     */
    public function setUsername(string $username): Config
    {
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
     */
    public function setPassword(string $password): Config
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return array
     */
    public function getBuckets(): array
    {
        return $this->buckets;
    }

    /**
     * @param array $buckets
     * @return Config
     */
    public function setBuckets(array $buckets): Config
    {
        $this->buckets = $buckets;
        return $this;
    }
}