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

namespace Phpfastcache\Drivers\Firestore;

use Google\Cloud\Firestore\FirestoreClient as GoogleFirestoreClient;
use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @see https://github.com/arangodb/arangodb-php/blob/devel/examples/init.php
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Config extends ConfigurationOption
{
    protected int $batchSize = 100;
    protected ?string $googleCloudProject = null;
    protected ?string $googleApplicationCredential = null;
    protected bool $allowEnvCredentialOverride = false;
    protected string $collectionName = 'phpfastcache';
    protected ?GoogleFirestoreClient $firestoreClient = null;

    /**
     * @see \Google\Cloud\Firestore\FirestoreClient::DEFAULT_DATABASE
     */
    protected string $databaseName = '(default)';

    /**
     * @inheritDoc
     */
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->googleCloudProject = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_CLOUD_PROJECT');
        $this->googleApplicationCredential = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_APPLICATION_CREDENTIALS');
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): Config
    {
        return $this->setProperty('batchSize', $batchSize);
    }

    /**
     * @return string
     * @deprecated As of 9.2, will be removed in v10.
     * @see self::getCollectionName()
     */
    public function getCollection(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     * @throws PhpfastcacheLogicException
     * @see self::setCollectionName()
     * @deprecated As of 9.2, will be removed in v10.
     */
    public function setCollection(string $collectionName): Config
    {
        if (isset($this->collectionName) && $collectionName !== $this->collectionName) {
            trigger_error('getCollection/setCollection methods are deprecated, use getCollectionName/setCollectionName instead', E_USER_DEPRECATED);
        }
        return $this->setProperty('collectionName', $collectionName);
    }

    /**
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setCollectionName(string $collectionName): Config
    {
        return $this->setProperty('collectionName', $collectionName);
    }

    /**
     * @return string
     */
    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    /**
     * @param string $databaseName
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setDatabaseName(string $databaseName): Config
    {
        return $this->setProperty('databaseName', $databaseName);
    }



    /**
     * @return string|null
     */
    public function getGoogleCloudProject(): ?string
    {
        return $this->googleCloudProject;
    }

    /**
     * @param string|null $googleCloudProject
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setGoogleCloudProject(?string $googleCloudProject): Config
    {
        if ($googleCloudProject !== null) {
            if (!getenv('GOOGLE_CLOUD_PROJECT')) {
                if (!$this->isAllowEnvCredentialOverride()) {
                    throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
                }
                \putenv("GOOGLE_CLOUD_PROJECT=$googleCloudProject");
            }
            return $this->setProperty('googleCloudProject', getenv('GOOGLE_CLOUD_PROJECT'));
        }
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGoogleApplicationCredential(): ?string
    {
        return $this->googleApplicationCredential;
    }

    /**
     * @param string|null $googleApplicationCredential
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setGoogleApplicationCredential(?string $googleApplicationCredential): Config
    {
        if ($googleApplicationCredential !== null) {
            if (!getenv('GOOGLE_APPLICATION_CREDENTIALS')) {
                if (!$this->isAllowEnvCredentialOverride()) {
                    throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
                }
                \putenv("GOOGLE_APPLICATION_CREDENTIALS=$googleApplicationCredential");
            }
            return $this->setProperty('googleApplicationCredential', getenv('GOOGLE_APPLICATION_CREDENTIALS'));
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowEnvCredentialOverride(): bool
    {
        return $this->allowEnvCredentialOverride;
    }

    /**
     * @param bool $allowEnvCredentialOverride
     * @return Config
     */
    public function setAllowEnvCredentialOverride(bool $allowEnvCredentialOverride): Config
    {
        return $this->setProperty('allowEnvCredentialOverride', $allowEnvCredentialOverride);
    }

    /**
     * @return GoogleFirestoreClient|null
     */
    public function getFirestoreClient(): ?GoogleFirestoreClient
    {
        return $this->firestoreClient;
    }

    /**
     * @param GoogleFirestoreClient|null $firestoreClient
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setRedisClient(?GoogleFirestoreClient $firestoreClient): Config
    {
        return $this->setProperty('firestoreClient', $firestoreClient);
    }
}
