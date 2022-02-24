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

namespace Phpfastcache\Drivers\Dynamodb;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @see https://github.com/arangodb/arangodb-php/blob/devel/examples/init.php
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class Config extends ConfigurationOption
{
    protected ?string $awsAccessKeyId = null;

    protected ?string $awsSecretAccessKey = null;

    protected bool $allowEnvCredentialOverride = false;

    protected ?string $endpoint = null; // List of endpoints here: https://docs.aws.amazon.com/general/latest/gr/ddb.html

    protected string $region;

    protected string $table;

    protected bool $debugEnabled = false;

    protected string $version = 'latest';

    protected string $partitionKey = ExtendedCacheItemPoolInterface::DRIVER_KEY_WRAPPER_INDEX;

    public function __construct(array $parameters = [])
    {
        parent::__construct($parameters);
        $this->awsAccessKeyId = $this->getSuperGlobalAccessor()('SERVER', 'AWS_ACCESS_KEY_ID');
        $this->awsSecretAccessKey = $this->getSuperGlobalAccessor()('SERVER', 'AWS_SECRET_ACCESS_KEY');
    }

    public function getAwsAccessKeyId(): ?string
    {
        return $this->awsAccessKeyId;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAwsAccessKeyId(?string $awsAccessKeyId): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (null !== $awsAccessKeyId) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override AWS environment variables.');
            }
            putenv("AWS_ACCESS_KEY_ID=$awsAccessKeyId");
            $this->awsAccessKeyId = $awsAccessKeyId;
        }

        return $this;
    }

    public function getAwsSecretAccessKey(): ?string
    {
        return $this->awsSecretAccessKey;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAwsSecretAccessKey(?string $awsSecretAccessKey): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (null !== $awsSecretAccessKey) {
            if (!$this->isAllowEnvCredentialOverride()) {
                throw new PhpfastcacheLogicException('You are not allowed to override AWS environment variables.');
            }
            putenv("AWS_SECRET_ACCESS_KEY=$awsSecretAccessKey");
            $this->awsSecretAccessKey = $awsSecretAccessKey;
        }

        return $this;
    }

    public function isAllowEnvCredentialOverride(): bool
    {
        return $this->allowEnvCredentialOverride;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setAllowEnvCredentialOverride(bool $allowEnvCredentialOverride): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->allowEnvCredentialOverride = $allowEnvCredentialOverride;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }

    /**
     * @param ?string $endpoint
     *
     * @throws PhpfastcacheLogicException
     */
    public function setEndpoint(?string $endpoint): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        if (!str_starts_with($endpoint, 'https://') && str_ends_with($endpoint, 'amazonaws.com')) {
            $endpoint = 'https://' . $endpoint;
        }
        $this->endpoint = $endpoint;

        return $this;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setRegion(string $region): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->region = $region;

        return $this;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setTable(string $table): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->table = $table;

        return $this;
    }

    public function isDebugEnabled(): bool
    {
        return $this->debugEnabled;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setDebugEnabled(bool $debugEnabled): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->debugEnabled = $debugEnabled;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setVersion(string $version): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->version = $version;

        return $this;
    }

    public function getPartitionKey(): string
    {
        return $this->partitionKey;
    }

    /**
     * @throws PhpfastcacheLogicException
     */
    public function setPartitionKey(string $partitionKey): self
    {
        $this->enforceLockedProperty(__FUNCTION__);
        $this->partitionKey = $partitionKey;

        return $this;
    }
}
