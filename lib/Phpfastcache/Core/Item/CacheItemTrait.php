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

use DateInterval;
use DateTime;
use DateTimeInterface;
use Phpfastcache\Event\EventManagerDispatcherTrait;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Util\ClassNamespaceResolverTrait;

trait CacheItemTrait
{
    use EventManagerDispatcherTrait;
    use ClassNamespaceResolverTrait;

    protected string $key;

    protected mixed $data;

    protected DateTimeInterface $expirationDate;

    protected DateTimeInterface $creationDate;

    protected DateTimeInterface $modificationDate;

    protected bool $isHit = false;

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        if (!$this->isHit()) {
            return null;
        }

        return $this->data;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function set(mixed $value): static
    {
        if ($value instanceof \Closure) {
            throw new PhpfastcacheInvalidArgumentException('The value set cannot be an instance of \\Closure.');
        }

        /**
         * @eventName CacheSaveDeferredItem
         * @param ExtendedCacheItemInterface $this
         * @param mixed $value
         *
         */
        $this->eventManager->dispatch('CacheItemSet', $this, new EventReferenceParameter($value, true));

        $this->data = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * @param bool $isHit
     * @return ExtendedCacheItemInterface
     */
    public function setHit(bool $isHit): ExtendedCacheItemInterface
    {
        $this->isHit = $isHit;

        return $this;
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        if ($expiration instanceof DateTimeInterface) {
            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAt', $this, $expiration);
            $this->expirationDate = $expiration;
        } else {
            throw new PhpfastcacheInvalidArgumentException('$expiration must be an object implementing the DateTimeInterface got: ' . \gettype($expiration));
        }

        return $this;
    }

    /**
     * @return $this
     * @throws PhpfastcacheInvalidArgumentException
     * @throws \Exception
     */
    public function expiresAfter(int|\DateInterval|null $time): static
    {
        if (\is_numeric($time)) {
            if ($time <= 0) {
                /**
                 * 5 years, however memcached or memory cached will gone when u restart it
                 * just recommended for sqlite. files
                 */
                $time = 30 * 24 * 3600 * 5;
            }

            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAfter', $this, $time);

            $this->expirationDate = (new DateTime())->add(new DateInterval(\sprintf('PT%dS', $time)));
        } elseif ($time instanceof DateInterval) {
            /**
             * @eventName CacheItemExpireAt
             * @param ExtendedCacheItemInterface $this
             * @param DateTimeInterface $expiration
             */
            $this->eventManager->dispatch('CacheItemExpireAfter', $this, $time);

            $this->expirationDate = (new DateTime())->add($time);
        } else {
            throw new PhpfastcacheInvalidArgumentException(\sprintf('Invalid date format, got "%s"', \gettype($time)));
        }

        return $this;
    }
}
