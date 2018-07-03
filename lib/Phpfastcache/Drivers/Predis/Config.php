<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace Phpfastcache\Drivers\Predis;

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
    protected $port = 6379;

    /**
     * @var string
     */
    protected $password = '';

    /**
     * @var int
     */
    protected $database = 0;

    /**
     * @var \Predis\Client
     */
    protected $predisClient;

    /**
     * @var string
     */
    protected $optPrefix = '';

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

    /**
     * @return null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param null $password
     * @return self
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return int
     */
    public function getDatabase(): int
    {
        return $this->database;
    }

    /**
     * @param int $database
     * @return Config
     */
    public function setDatabase(int $database): self
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @return array
     */
    public function getPredisConfigArray(): array
    {
        return [
            'host' => $this->getHost(),
            'port' => $this->getPort(),
            'password' => $this->getPassword() ?: null,
            'database' => $this->getDatabase(),
        ];
    }

    /**
     * @return \Predis\Client|null
     */
    public function getPredisClient()
    {
        return $this->predisClient;
    }

    /**
     * @param \Predis\Client $predisClient |null
     * @return Config
     */
    public function setPredisClient(\Predis\Client $predisClient = null): Config
    {
        $this->predisClient = $predisClient;
        return $this;
    }

    /**
     * @return string
     * @since 7.0.2
     */
    public function getOptPrefix(): string
    {
        return $this->optPrefix;
    }

    /**
     * @param string $optPrefix
     * @return Config
     * @since 7.0.2
     */
    public function setOptPrefix(string $optPrefix): Config
    {
        $this->optPrefix = trim($optPrefix);
        return $this;
    }
}