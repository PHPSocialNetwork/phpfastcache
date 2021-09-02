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

namespace Phpfastcache\Drivers\Cookie;

use Phpfastcache\Core\Pool\{ExtendedCacheItemPoolInterface, TaggableCacheItemPoolTrait};
use Phpfastcache\Core\Item\ExtendedCacheItemInterface;
use Phpfastcache\Entities\DriverStatistic;
use Phpfastcache\Exceptions\{PhpfastcacheDriverException, PhpfastcacheInvalidArgumentException};
use Psr\Cache\CacheItemInterface;


/**
 * @property Config $config
 */
class Driver implements ExtendedCacheItemPoolInterface
{
    use TaggableCacheItemPoolTrait;


    protected const PREFIX = 'PFC_';

    /**
     * @return bool
     */
    public function driverCheck(): bool
    {
        if (!$this->getConfig()->isAwareOfUntrustableData()) {
            throw new PhpfastcacheDriverException(
                'You have to setup the config "awareOfUntrustableData" to "TRUE" to confirm that you are aware that this driver does not use reliable storage as it may be corrupted by end-user.'
            );
        }
        return function_exists('setcookie');
    }

    /**
     * @return DriverStatistic
     */
    public function getStats(): DriverStatistic
    {
        $size = 0;
        $stat = new DriverStatistic();
        $stat->setData(\json_encode($_COOKIE, JSON_THROW_ON_ERROR));

        /**
         * Only count PFC Cookie
         */
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, self::PREFIX) === 0) {
                $size += strlen($value);
            }
        }

        $stat->setSize($size);

        return $stat;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return null|array
     * @throws PhpfastcacheDriverException
     */
    protected function driverRead(ExtendedCacheItemInterface $item): ?array
    {
        $this->driverConnect();
        $keyword = self::PREFIX . $item->getKey();
        $x = isset($_COOKIE[$keyword]) ? json_decode($_COOKIE[$keyword], true) : false;

        if ($x == false) {
            return null;
        }

        if (!is_scalar($this->driverUnwrapData($x)) && !is_null($this->driverUnwrapData($x))) {
            throw new PhpfastcacheDriverException('Hacking attempt: The decoding returned a non-scalar value, Cookie driver does not allow this.');
        }

        return $x;
    }

    /**
     * @return bool
     */
    protected function driverConnect(): bool
    {
        return !(!array_key_exists('_pfc', $_COOKIE) && !@setcookie('_pfc', '1', 10));
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverWrite(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $this->driverConnect();
        $keyword = self::PREFIX . $item->getKey();
        $v = json_encode($this->driverPreWrap($item));

        if ($this->getConfig()->getLimitedMemoryByObject() !== null && strlen($v) > $this->getConfig()->getLimitedMemoryByObject()) {
            return false;
        }

        return setcookie($keyword, $v, $item->getExpirationDate()->getTimestamp(), '/');
    }

    /**
     * @param string $key
     * @return int
     */
    protected function driverReadExpirationDate($key): int
    {
        $this->driverConnect();
        $keyword = self::PREFIX . $key;
        $x = isset($_COOKIE[$keyword]) ? $this->decode(json_decode($_COOKIE[$keyword])->t) : 0;

        return $x ? $x - time() : $x;
    }

    /**
     * @param ExtendedCacheItemInterface $item
     * @return bool
     * @throws PhpfastcacheInvalidArgumentException
     */
    protected function driverDelete(ExtendedCacheItemInterface $item): bool
    {
        $this->assertCacheItemType($item, Item::class);

        $this->driverConnect();
        $keyword = self::PREFIX . $item->getKey();
        $_COOKIE[$keyword] = null;

        return @setcookie($keyword, null, -10);
    }

    /**
     * @return bool
     */
    protected function driverClear(): bool
    {
        $return = null;
        $this->driverConnect();
        foreach ($_COOKIE as $keyword => $value) {
            if (strpos($keyword, self::PREFIX) !== false) {
                $_COOKIE[$keyword] = null;
                $result = @setcookie($keyword, null, -10);
                if ($return !== false) {
                    $return = $result;
                }
            }
        }

        return $return;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}
