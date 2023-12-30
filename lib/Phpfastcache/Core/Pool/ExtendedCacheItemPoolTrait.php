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

namespace Phpfastcache\Core\Pool;

use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Event\Event;
use Phpfastcache\Event\EventReferenceParameter;
use Phpfastcache\Exceptions\PhpfastcacheCoreException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;

trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait;
    use AggregatablePoolTrait;

    /**
     * @inheritDoc
     * @param string $pattern
     * @return array<string, mixed>
     */
    public function getAllItems(string $pattern = ''): iterable
    {
        $driverReadAllKeysCallback = fn (string $pattern): iterable => $this->driverReadAllKeys($pattern);

        /**
         * This event allow you to customize the callback and wrap it to an invoker
         * like SebastianBergmann\Invoke\Invoke, so you can set up custom timeouts.
         */
        $this->eventManager->dispatch(Event::CACHE_GET_ALL_ITEMS, $this, new EventReferenceParameter($driverReadAllKeysCallback));
        $keys = $driverReadAllKeysCallback($pattern);

        if (count($keys) > 0) {
            return $this->getItems($keys instanceof \Traversable ? iterator_to_array($keys) : $keys);
        }

        return [];
    }

    /**
     * @inheritDoc
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function getItemsAsJsonString(array $keys = [], int $options = \JSON_THROW_ON_ERROR, int $depth = 512): string
    {
        return \json_encode(
            \array_map(
                static fn(CacheItemInterface $item) => $item->get(),
                \array_values($this->getItems($keys))
            ),
            $options,
            $depth
        );
    }

    public function detachAllItems(): static
    {
        foreach ($this->itemInstances as $item) {
            $this->detachItem($item);
        }

        return $this;
    }

    public function detachItem(CacheItemInterface $item): static
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            $this->deregisterItem($item->getKey());
        }

        return $this;
    }

    /**
     * @param ExtendedCacheItemInterface ...$items
     * @return bool
     * @throws PhpfastcacheCoreException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    public function saveMultiple(ExtendedCacheItemInterface ...$items): bool
    {
        $this->eventManager->dispatch(Event::CACHE_SAVE_MULTIPLE_ITEMS, $this, new EventReferenceParameter($items));

        if (\count($items)) {
            foreach ($items as $item) {
                $this->save($item);
            }
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return '';
    }

    /**
     * @throws PhpfastcacheInvalidArgumentException
     */
    public function throwUnsupportedDriverReadAllPattern(string $linkReference = ''): void
    {
        throw new PhpfastcacheInvalidArgumentException(
            sprintf(
                '%s does not support a pattern argument.%s',
                $this->getDriverName(),
                $linkReference ? " See $linkReference" : ''
            )
        );
    }
}
