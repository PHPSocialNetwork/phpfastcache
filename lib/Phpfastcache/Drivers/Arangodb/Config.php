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

namespace Phpfastcache\Drivers\Arangodb;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @see https://github.com/arangodb/arangodb-php/blob/devel/examples/init.php
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Config extends ConfigurationOption
{
    protected string $database;
    protected string $collection;

    /**
     * @var string|array<string>
     *  HTTP ENDPOINT: ssl://127.0.0.1:8529
     *  SSL ENDPOINT: ssl://127.0.0.1:8529
     *  UNIX ENDPOINT: unix:///tmp/arangodb.sock
     *  Failover ENDPOINTS: ['tcp://127.0.0.1:8529', 'tcp://127.0.0.1:8529']
     */
    protected string|array $endpoint = 'tcp://127.0.0.1:8529';

    // enum{'Close', 'Keep-Alive'}
    protected string $connection = 'Keep-Alive';

    // enum{'Bearer', 'Basic'}
    protected string $authType = 'Basic';


    protected string $authUser = '';
    protected string $authPasswd = '';
    protected ?string $authJwt = null;

    /** Do not create unknown collections automatically */
    protected bool $autoCreate = false;

    protected int $connectTimeout = 3;
    protected int $requestTimeout = 5;
    protected string $updatePolicy = 'last';
    protected bool $verifyCert = true;
    protected bool $selfSigned = true;

    /** @see https://www.openssl.org/docs/manmaster/man1/ciphers.html */
    protected string $ciphers = 'DEFAULT';

    protected ?\Closure $traceFunction = null;

    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setDatabase(string $database): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->database = $database;
        return $this;
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setCollection(string $collection): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return string|array<string>
     */
    public function getEndpoint(): string|array
    {
        return $this->endpoint;
    }

    /**
     * @param string|array<string> $endpoint
     * @return $this
     * @throws PhpfastcacheLogicException
     */
    public function setEndpoint(string|array $endpoint): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->endpoint = $endpoint;
        return $this;
    }

    public function getConnection(): string
    {
        return $this->connection;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setConnection(string $connection): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->connection = $connection;
        return $this;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAuthType(string $authType): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->authType = $authType;
        return $this;
    }

    public function getAuthUser(): string
    {
        return $this->authUser;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAuthUser(string $authUser): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->authUser = $authUser;
        return $this;
    }

    public function getAuthPasswd(): string
    {
        return $this->authPasswd;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAuthPasswd(string $authPasswd): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->authPasswd = $authPasswd;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAuthJwt(): ?string
    {
        return $this->authJwt;
    }

    /**
     * @param string|null $authJwt
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setAuthJwt(?string $authJwt): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->authJwt = $authJwt;
        return $this;
    }

    public function isAutoCreate(): bool
    {
        return $this->autoCreate;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAutoCreate(bool $autoCreate): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->autoCreate = $autoCreate;
        return $this;
    }

    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setConnectTimeout(int $connectTimeout): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    public function getRequestTimeout(): int
    {
        return $this->requestTimeout;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setRequestTimeout(int $requestTimeout): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->requestTimeout = $requestTimeout;
        return $this;
    }

    public function getUpdatePolicy(): string
    {
        return $this->updatePolicy;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setUpdatePolicy(string $updatePolicy): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->updatePolicy = $updatePolicy;
        return $this;
    }

    public function isVerifyCert(): bool
    {
        return $this->verifyCert;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setVerifyCert(bool $verifyCert): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->verifyCert = $verifyCert;
        return $this;
    }

    public function isSelfSigned(): bool
    {
        return $this->selfSigned;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setSelfSigned(bool $selfSigned): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->selfSigned = $selfSigned;
        return $this;
    }

    public function getCiphers(): string
    {
        return $this->ciphers;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setCiphers(string $ciphers): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->ciphers = $ciphers;
        return $this;
    }

    /**
     * @return \Closure|null
     */
    public function getTraceFunction(): ?\Closure
    {
        return $this->traceFunction;
    }

    /**
     * @param \Closure|null $traceFunction
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setTraceFunction(?\Closure $traceFunction): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->traceFunction = $traceFunction;
        return $this;
    }
}
