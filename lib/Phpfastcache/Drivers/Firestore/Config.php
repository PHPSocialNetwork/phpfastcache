<?php
/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Firestore;

use Phpfastcache\Config\ConfigurationOption;
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

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->googleCloudProject = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_CLOUD_PROJECT');
        $this->googleApplicationCredential = $this->getSuperGlobalAccessor()('SERVER', 'GOOGLE_APPLICATION_CREDENTIALS');
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setCollection(string $collection): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->collection = $collection;

        return $this;
    }

    public function getGoogleCloudProject(): ?string
    {
        return $this->googleCloudProject;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setGoogleCloudProject(?string $googleCloudProject): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (null !== $googleCloudProject) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
            }
            putenv("GOOGLE_CLOUD_PROJECT=$googleCloudProject");
            $this->googleApplicationCredential = $googleCloudProject;
        }

        return $this;
    }

    public function getGoogleApplicationCredential(): ?string
    {
        return $this->googleApplicationCredential;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setGoogleApplicationCredential(?string $googleApplicationCredential): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (null !== $googleApplicationCredential) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override GCP environment variables.');
            }
            putenv("GOOGLE_APPLICATION_CREDENTIALS=$googleApplicationCredential");
            $this->googleApplicationCredential = $googleApplicationCredential;
        }

        return $this;
    }

    public function isAllowEnvCredentialOverride(): bool
    {
        return $this->allowEnvCredentialOverride;
    }

    public function setAllowEnvCredentialOverride(bool $allowEnvCredentialOverride): self
    {
        $this->allowEnvCredentialOverride = $allowEnvCredentialOverride;

        return $this;
    }
}
