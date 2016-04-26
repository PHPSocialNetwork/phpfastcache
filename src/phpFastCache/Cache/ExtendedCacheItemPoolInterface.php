<?php
namespace phpFastCache\Cache;

use Psr\Cache\CacheItemInterface;

interface ExtendedCacheItemPoolInterface
{
    public function setItem(CacheItemInterface $item);

    /**
     * @return bool
     */
    public function getStats();
}