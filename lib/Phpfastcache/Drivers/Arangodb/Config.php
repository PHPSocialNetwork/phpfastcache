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
    public function setDatabase(string $database): static
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
    public function setCollection(string $collection): static
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
     * @throws PhpfastcacheLogicException
     */
    public function setEndpoint(string|array $endpoint): static
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
    public function setConnection(string $connection): static
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
    public function setAuthType(string $authType): static
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
    public function setAuthUser(string $authUser): static
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
    public function setAuthPasswd(string $authPasswd): static
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
     * @throws PhpfastcacheLogicException
     */
    public function setAuthJwt(?string $authJwt): static
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
    public function setAutoCreate(bool $autoCreate): static
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
    public function setConnectTimeout(int $connectTimeout): static
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
    public function setRequestTimeout(int $requestTimeout): static
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
    public function setUpdatePolicy(string $updatePolicy): static
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
    public function setVerifyCert(bool $verifyCert): static
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
    public function setSelfSigned(bool $selfSigned): static
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
    public function setCiphers(string $ciphers): static
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
     * @throws PhpfastcacheLogicException
     */
    public function setTraceFunction(?\Closure $traceFunction): static
    {
        return $this->setProperty('traceFunction', $traceFunction);
    }
}
