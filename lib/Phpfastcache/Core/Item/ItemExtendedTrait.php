<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Core\Item;

use Countable;
use DateTime;
use DateTimeInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\{PhpfastcacheInvalidArgumentException, PhpfastcacheInvalidArgumentTypeException, PhpfastcacheLogicException};
use Phpfastcache\Util\ClassNamespaceResolverTrait;


/**
 * Class ItemExtendedTrait
 * @package phpFastCache\Core\Item
 * @property DateTimeInterface $expirationDate Expiration date of the item
 * @property DateTimeInterface $creationDate Creation date of the item
 * @property DateTimeInterface $modificationDate Modification date of the item
 * @property mixed $data Data of the item
 * @property bool $fetched Fetch flag status
 * @property string $key The item key
 */
trait ItemExtendedTrait
{
    use ClassNamespaceResolverTrait;
    use TaggableCacheItemTrait;

    /********************
     *
     * PSR-6 Extended Methods
     *
     *******************/

    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected $driver;

    /**
     * @var string
     */
    protected $encodedKey;

    /**
     * Item constructor.
     * @param ExtendedCacheItemPoolInterface $driver
     * @param $key
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function __construct(ExtendedCacheItemPoolInterface $driver, $key)
    {
        if (\is_string($key)) {
            $this->key = $key;
            $this->driver = $driver;
            if($driver->getConfig()->isUseStaticItemCaching()){
                $this->driver->setItem($this);
            }
            $this->expirationDate = new DateTime();
            if ($this->driver->getConfig()->isItemDetailedDate()) {
                $this->creationDate = new DateTime();
                $this->modificationDate = new DateTime();
            }
        } else {
            throw new PhpfastcacheInvalidArgumentTypeException('string', $key);
        }
    }

    /**
     * @return string
     */
    public function getEncodedKey(): string
    {
        if (!$this->encodedKey) {
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
     * @return DateTimeInterface
     */
    public function getExpirationDate(): DateTimeInterface
    {
        return $this->expirationDate;
    }

    /**
     * Alias of expireAt() with forced $expiration param
     *
     * @param DateTimeInterface $expiration
     *   The point in time after which the item MUST be considered expired.
     *   If null is passed explicitly, a default value MAY be used. If none is set,
     *   the value should be stored permanently or for as long as the
     *   implementation allows.
     *
     * @return ExtendedCacheItemInterface
     *   The called object.
     */
    public function setExpirationDate(DateTimeInterface $expiration): ExtendedCacheItemInterface
    {
        return $this->expiresAt($expiration);
    }

    /**
     * @return DateTimeInterface
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
     * @param DateTimeInterface $date
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheLogicException
     */
    public function setCreationDate(DateTimeInterface $date): ExtendedCacheItemInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $this->creationDate = $date;
            return $this;
        }

        throw new PhpfastcacheLogicException('Cannot access to the creation date when the "itemDetailedDate" configuration is disabled.');
    }

    /**
     * @return DateTimeInterface
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
     * @param DateTimeInterface $date
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheLogicException
     */
    public function setModificationDate(DateTimeInterface $date): ExtendedCacheItemInterface
    {
        if ($this->driver->getConfig()->isItemDetailedDate()) {
            $this->modificationDate = $date;
            return $this;
        }

        throw new PhpfastcacheLogicException('Cannot access to the modification date when the "itemDetailedDate" configuration is disabled.');
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return \max(0, $this->expirationDate->getTimestamp() - \time());
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expirationDate->getTimestamp() < (new DateTime())->getTimestamp();
    }

    /**
     * @return bool
     */
    public function isNull(): bool
    {
        return $this->data === null;
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
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
                if (\is_array($this->data) || $this->data instanceof Countable) {
                    return \count($this->data);
                }
                break;

            case 'string':
                return \strlen($this->data);
                break;
        }

        return -1;
    }


    /**
     * @param int $step
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function increment($step = 1): ExtendedCacheItemInterface
    {
        if (\is_int($step)) {
            $this->fetched = true;
            $this->data += $step;
        } else {
            throw new PhpfastcacheInvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param int $step
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function decrement($step = 1): ExtendedCacheItemInterface
    {
        if (\is_int($step)) {
            $this->fetched = true;
            $this->data -= $step;
        } else {
            throw new PhpfastcacheInvalidArgumentException('$step must be numeric.');
        }

        return $this;
    }

    /**
     * @param array|string $data
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function append($data): ExtendedCacheItemInterface
    {
        if (\is_array($this->data)) {
            $this->data[] = $data;
        } else {
            if (\is_string($data)) {
                $this->data .= (string)$data;
            } else {
                throw new PhpfastcacheInvalidArgumentException('$data must be either array nor string.');
            }
        }

        return $this;
    }


    /**
     * @param array|string $data
     * @return ExtendedCacheItemInterface
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function prepend($data): ExtendedCacheItemInterface
    {
        if (\is_array($this->data)) {
            \array_unshift($this->data, $data);
        } else {
            if (\is_string($data)) {
                $this->data = (string)$data . $this->data;
            } else {
                throw new PhpfastcacheInvalidArgumentException('$data must be either array nor string.');
            }
        }

        return $this;
    }

    /**
     * Return the data as a well-formatted string.
     * Any scalar value will be casted to an array
     * @param int $option \json_encode() options
     * @param int $depth \json_encode() depth
     * @return string
     */
    public function getDataAsJsonString(int $option = 0, int $depth = 512): string
    {
        $data = $this->get();

        if (\is_object($data) || \is_array($data)) {
            $data = \json_encode($data, $option, $depth);
        } else {
            $data = \json_encode([$data], $option, $depth);
        }

        return \json_encode($data, $option, $depth);
    }

    /**
     * Implements \JsonSerializable interface
     * @return mixed
     */
    #[\ReturnTypeWillChange] // PHP 8.1 compatibility
    public function jsonSerialize()
    {
        return $this->get();
    }

    /**
     * @param ExtendedCacheItemPoolInterface $driverPool
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function doesItemBelongToThatDriverBackend(ExtendedCacheItemPoolInterface $driverPool): bool
    {
        return $driverPool->getClassNamespace() === $this->getClassNamespace();
    }

    /**
     * @return array
     * @todo Is it still useful ??
     *
     * Prevent recursions for Debug (php 5.6+)
     */
    final public function __debugInfo()
    {
        $info = \get_object_vars($this);
        $info['driver'] = 'object(' . \get_class($info['driver']) . ')';

        return $info;
    }
}
