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

namespace Phpfastcache\Core\Item;

use DateTime;
use DateTimeInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Event\EventManagerInterface;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

trait ExtendedCacheItemTrait
{
    use CacheItemTrait;

    protected ExtendedCacheItemPoolInterface $driver;

    protected string $encodedKey;

    /**
     * Item constructor.
     * @param ExtendedCacheItemPoolInterface $driver
     * @param string $key
     * @param EventManagerInterface $em
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(ExtendedCacheItemPoolInterface $driver, string $key, EventManagerInterface $em)
    {
        $this->data = null;
        $this->key = $key;
        $this->setEventManager($em);
        $this->setDriver($driver);
        if ($driver->getConfig()->isUseStaticItemCaching()) {
            $this->driver->setItem($this);
        }
        $this->expirationDate = new DateTime();
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $this->creationDate = new DateTime();
            $this->modificationDate = new DateTime();
        }
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driver
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setDriver(ExtendedCacheItemPoolInterface $driver): ExtendedCacheItemInterface
    {
        $driverClass = $this->getDriverClass();
        if ($driver instanceof $driverClass) {
            $this->driver = $driver;

            return $this;
        }

        throw new PhpfastcacheInvalidArgumentException(\sprintf('Invalid driver instance "%s" for cache item "%s"', $driver::class, static::class));
    }

    /**
     * @inheritDoc
     */
    public function getEncodedKey(): string
    {
        // Only calculate the encoded key on demand to save resources
        if (!isset($this->encodedKey)) {
            $keyHashFunction = $this->driver->getConfig()->getDefaultKeyHashFunction();

            if ($keyHashFunction) {
                $this->encodedKey = $keyHashFunction($this->getKey());
            } else {
                $this->encodedKey = $this->getKey();
            }
        }

        return $this->encodedKey;
    }

    /**
     * @inheritDoc
     */
    public function getRawValue(): mixed
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getExpirationDate(): DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function setExpirationDate(DateTimeInterface $expiration): ExtendedCacheItemInterface
    {
        return $this->expiresAt($expiration);
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheLogicException
     */
    public function getCreationDate(): DateTimeInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            return $this->creationDate;
        }

        throw new PhpfastcacheLogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheLogicException
     */
    public function setCreationDate(DateTimeInterface $date): ExtendedCacheItemInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $this->creationDate = $this->demutateDatetime($date);
            return $this;
        }

        throw new PhpfastcacheLogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheLogicException
     */
    public function getModificationDate(): DateTimeInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            return $this->modificationDate;
        }

        throw new PhpfastcacheLogicException('Cannot access to the modification date when the "itemDetailedDate" configuration is disabled.');
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheLogicException
     */
    public function setModificationDate(DateTimeInterface $date): ExtendedCacheItemInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $this->modificationDate = $this->demutateDatetime($date);
            return $this;
        }

        throw new PhpfastcacheLogicException('Cannot access to the modification date when the "itemDetailedDate" configuration is disabled.');
    }

    public function getTtl(): int
    {
        return \max(0, $this->expirationDate->getTimestamp() - \time());
    }

    public function isExpired(): bool
    {
        return $this->getTtl() <= 0;
    }

    public function isNull(): bool
    {
        return $this->get() === null;
    }

    public function isEmpty(): bool
    {
        return empty($this->get());
    }

    /**
     * Return the data length:
     * Either the string length if it's a string (binary mode)
     * # or the number of element (count) if it's an array
     * # or the number returned by count() if it's an object implementing \Countable interface
     * # -1 for anything else
     * @return int
     */
    public function getLength(): int
    {
        switch (\gettype($this->data)) {
            case 'array':
            case 'object':
                if (\is_countable($this->data)) {
                    return \count($this->data);
                }
                break;

            case 'string':
                return \strlen($this->data);
        }

        return -1;
    }

    /**
     * @throws PhpfastcacheInvalidTypeException
     */
    public function increment(int $step = 1): ExtendedCacheItemInterface
    {
        if ($this->data !== null && !\is_numeric($this->data)) {
            throw new PhpfastcacheInvalidTypeException(\sprintf('Cannot increment on a "%s" type.', \gettype($this->data)));
        }

        $this->data += $step;

        return $this;
    }

    /**
     * @throws PhpfastcacheInvalidTypeException
     */
    public function decrement(int $step = 1): ExtendedCacheItemInterface
    {
        if ($this->data !== null && !\is_numeric($this->data)) {
            throw new PhpfastcacheInvalidTypeException(\sprintf('Cannot decrement on a "%s" type.', \gettype($this->data)));
        }

        $this->data -= $step;

        return $this;
    }

    /**
     * @throws PhpfastcacheInvalidTypeException
     */
    public function append(array|string $data): ExtendedCacheItemInterface
    {
        if ($this->data !== null && !\is_string($this->data) && !\is_array($this->data)) {
            throw new PhpfastcacheInvalidTypeException(\sprintf('Cannot append on a "%s" type.', \gettype($this->data)));
        }

        if (\is_array($this->data)) {
            $this->data[] = $data;
        } else {
            $this->data .= $data;
        }

        return $this;
    }

    /**
     * @throws PhpfastcacheInvalidTypeException
     */
    public function prepend(array|string $data): ExtendedCacheItemInterface
    {
        if ($this->data !== null && !\is_string($this->data) && !\is_array($this->data)) {
            throw new PhpfastcacheInvalidTypeException(\sprintf('Cannot prepend on a "%s" type.', \gettype($this->data)));
        }

        if (\is_array($this->data)) {
            \array_unshift($this->data, $data);
        } else {
            $this->data = $data . $this->data;
        }

        return $this;
    }

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     * @param int $options \json_encode() options
     * @param int $depth \json_encode() depth
     * @return string
     */
    public function getDataAsJsonString(int $options = JSON_THROW_ON_ERROR, int $depth = 512): string
    {
        $data = $this->get();

        if (\is_object($data) || \is_array($data)) {
            $data = \json_encode($data, $options, $depth);
        } else {
            $data = \json_encode([$data], $options, $depth);
        }

        return \json_encode($data, $options, $depth);
    }

    public function jsonSerialize(): mixed
    {
        return $this->get();
    }

    public function doesItemBelongToThatDriverBackend(ExtendedCacheItemPoolInterface $driverPool): bool
    {
        return $driverPool::getClassNamespace() === self::getClassNamespace();
    }

    abstract protected function getDriverClass(): string;
}
