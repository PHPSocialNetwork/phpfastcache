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

namespace Phpfastcache\Drivers\Wincache;

use DateTime;
use Phpfastcache\Cluster\AggregatablePoolInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Core\Pool\TaggableCacheItemPoolTrait;
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * @deprecated will be removed as of v10 due to the lack of updates to PHP8 as officially stated by PHP: https://www.php.net/manual/en/install.windows.recommended.php
 * @method Config getConfig()
 */
class Driver implements AggregatablePoolInterface
{
    use TaggableCacheItemPoolTrait;

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        return extension_loaded('wincache') && function_exists('wincache_ucache_set');
    }

    public function getHelp(): string
    {
        return <<<HELP
Wincache PECL extension is not maintained anymore and has been superseded. Check sourceforge for more informations: https://sourceforge.net/projects/wincache/'
HELP;
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $memInfo = wincache_ucache_meminfo();
        $info = wincache_ucache_info();
        $date = (new DateTime())->setTimestamp(time() - $info['total_cache_uptime']);

        return (new DriverStatistic())
            ->setInfo(sprintf("The Wincache daemon is up since %s.\n For more information see RawData.", $date->format(DATE_RFC2822)))
            ->setSize($memInfo['memory_free'] - $memInfo['memory_total'])
            ->setData(implode(', ', array_keys($this->itemInstances)))
            ->setRawData($memInfo);
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
        $val = wincache_ucache_get($item->getKey(), $suc);

        if ($suc === false || empty($val)) {
            return null;
        }

        return $val;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return mixed
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {

        return wincache_ucache_set($item->getKey(), $this->driverPreWrap($item), $item->getTtl());
    }

    /**
     * @param string $key
     * @param string $encodedKey
     * @return bool
     */
    protected function driverDelete(string $key, string $encodedKey): bool
    {
        return wincache_ucache_delete($key);
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        return wincache_ucache_clear();
    }
}
