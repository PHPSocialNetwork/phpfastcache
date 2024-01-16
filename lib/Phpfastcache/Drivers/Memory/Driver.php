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

namespace Phpfastcache\Drivers\Memory;

use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidTypeException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Psr\Cache\CacheItemInterface;

/**
 * Class Driver
 * @method Config getConfig()
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $staticStack = [];

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return true;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return ?array<string, mixed>
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        return $this->staticStack[$item->getKey()] ?? null;
    }

    /**
     * @return array<int, string>
     */
    protected function driverReadAllKeys(string $pattern = ''): iterable
    {
        return array_filter(array_keys($this->staticStack), function (string $key) use ($pattern) {
            return $pattern
                ? preg_match('/' . str_replace('*', '(.*)', $pattern) . '/', $key)
                : true;
        });
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {

        $this->staticStack[$item->getKey()] = $this->driverPreWrap($item);
        return true;
    }

    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        if (isset($this->staticStack[$key])) {
            unset($this->staticStack[$key]);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        unset($this->staticStack);
        $this->staticStack = [];
        return true;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $stat = new DriverStatistic();
        $stat->setInfo('[Memstatic] A memory static driver')
            ->setSize(mb_strlen(serialize($this->staticStack)))
            ->setRawData($this->staticStack);

        return $stat;
    }
}
