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

namespace Phpfastcache\Core\Pool;

use Phpfastcache\Entities\DriverIO;
use Phpfastcache\Exceptions\{PhpfastcacheLogicException};
use Psr\Cache\CacheItemInterface;


/**
 * Trait ExtendedCacheItemPoolTrait
 * @package Phpfastcache\Core\Pool
 */
trait ExtendedCacheItemPoolTrait
{
    use CacheItemPoolTrait;
    use AbstractDriverPoolTrait;

    /**
     * @var DriverIO
     */
    protected $IO;

    /**
     * @inheritdoc
     */
    public function getItemsAsJsonString(array $keys = [], int $option = 0, int $depth = 512): string
    {
        $callback = static function (CacheItemInterface $item) {
            return $item->get();
        };
        return \json_encode(\array_map($callback, \array_values($this->getItems($keys))), $option, $depth);
    }

    /**
     * @inheritdoc
     */
    public function detachAllItems()
    {
        foreach ($this->itemInstances as $item) {
            $this->detachItem($item);
        }
    }

    /**
     * @param CacheItemInterface $item
     * @return void
     */
    public function detachItem(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            $this->deregisterItem($item->getKey());
        }
    }

    /**
     * @param string $item
     * @internal This method de-register an item from $this->itemInstances
     */
    protected function deregisterItem(string $item)
    {
        unset($this->itemInstances[$item]);

        if (\gc_enabled()) {
            \gc_collect_cycles();
        }
    }

    /**
     * @inheritdoc
     */
    public function attachItem(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()]) && \spl_object_hash($item) !== \spl_object_hash($this->itemInstances[$item->getKey()])) {
            throw new PhpfastcacheLogicException(
                'The item already exists and cannot be overwritten because the Spl object hash mismatches ! You probably tried to re-attach a detached item which has been already retrieved from cache.'
            );
        }

        $this->itemInstances[$item->getKey()] = $item;
    }

    /**
     * Returns true if the item exists, is attached and the Spl Hash matches
     * Returns false if the item exists, is attached and the Spl Hash mismatches
     * Returns null if the item does not exists
     *
     * @param CacheItemInterface $item
     * @return bool|null
     */
    public function isAttached(CacheItemInterface $item)
    {
        if (isset($this->itemInstances[$item->getKey()])) {
            return \spl_object_hash($item) === \spl_object_hash($this->itemInstances[$item->getKey()]);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function saveMultiple(...$items): bool
    {
        if (isset($items[0]) && \is_array($items[0])) {
            foreach ($items[0] as $item) {
                $this->save($item);
            }
            return true;
        }

        if (\is_array($items)) {
            foreach ($items as $item) {
                $this->save($item);
            }
            return true;
        }
        return false;
    }

    /**
     * @return DriverIO
     */
    public function getIO(): DriverIO
    {
        return $this->IO;
    }

    /**
     * @return string
     */
    public function getHelp(): string
    {
        return '';
    }
}
