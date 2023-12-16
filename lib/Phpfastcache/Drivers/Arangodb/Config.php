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
        return $this->setProperty('database', $database);
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
        return $this->setProperty('collection', $collection);
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
        return $this->setProperty('endpoint', $endpoint);
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
        return $this->setProperty('connection', $connection);
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
        return $this->setProperty('authType', $authType);
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
        return $this->setProperty('authUser', $authUser);
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
        return $this->setProperty('authPasswd', $authPasswd);
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
        return $this->setProperty('authJwt', $authJwt);
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
        return $this->setProperty('autoCreate', $autoCreate);
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
        return $this->setProperty('connectTimeout', $connectTimeout);
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
        return $this->setProperty('requestTimeout', $requestTimeout);
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
        return $this->setProperty('updatePolicy', $updatePolicy);
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
        return $this->setProperty('verifyCert', $verifyCert);
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
        return $this->setProperty('selfSigned', $selfSigned);
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
        return $this->setProperty('ciphers', $ciphers);
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
        return $this->setProperty('traceFunction', $traceFunction);
    }
}
