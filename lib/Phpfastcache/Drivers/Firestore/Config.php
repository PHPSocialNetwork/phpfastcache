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

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @see https://github.com/arangodb/arangodb-php/blob/devel/examples/init.php
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Config extends ConfigurationOption
{
    protected ?string $googleCloudProject = null;
    protected ?string $googleApplicationCredential = null;
    protected bool $allowEnvCredentialOverride = false;
    protected string $collection;

    /**
     * @inheritDoc
     */
    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->googleCloudProject = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_CLOUD_PROJECT');
        $this->googleApplicationCredential = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_APPLICATION_CREDENTIALS');
    }

    /**
     * @return string
     */
    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * @param string $collection
     * @return Config
     * @throws PhpfastcacheLogicException
     */
    public function setCollection(string $collection): Config
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collection = $collection;
        return $this;
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
        $this->enforceLockedProperty(__FUNCTION__);
        if ($googleCloudProject !== null) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
            }
            \putenv("GOOGLE_CLOUD_PROJECT=$googleCloudProject");
            $this->googleApplicationCredential = $googleCloudProject;
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
        $this->enforceLockedProperty(__FUNCTION__);
        if ($googleApplicationCredential !== null) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
            }
            \putenv("GOOGLE_APPLICATION_CREDENTIALS=$googleApplicationCredential");
            $this->googleApplicationCredential = $googleApplicationCredential;
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
        $this->allowEnvCredentialOverride = $allowEnvCredentialOverride;
        return $this;
    }
}
