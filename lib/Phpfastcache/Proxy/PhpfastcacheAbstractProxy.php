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

namespace Phpfastcache\Proxy;

use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheDriverCheckException;
use Phpfastcache\Exceptions\PhpfastcacheDriverException;
use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;

/**
 * Handle methods using annotations for IDE
 * because they're handled by __call()
 * Check out ExtendedCacheItemInterface to see all
 * the drivers methods magically implemented
 */
abstract class PhpfastcacheAbstractProxy implements PhpfastcacheAbstractProxyInterface
{
    /**
     * @var ExtendedCacheItemPoolInterface
     */
    protected ExtendedCacheItemPoolInterface $instance;

    /**
     * PhpfastcacheAbstractProxy constructor.
     * @param string $driver
     * @param null|ConfigurationOptionInterface $config
     * @throws PhpfastcacheDriverCheckException
     * @throws PhpfastcacheDriverException
     * @throws PhpfastcacheDriverNotFoundException
     * @throws PhpfastcacheLogicException
     */
    public function __construct(string $driver, ?ConfigurationOptionInterface $config = null)
    {
        $this->instance = CacheManager::getInstance($driver, $config);
    }

    /**
     * @inheritDoc
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->instance->$name(...$args);
    }
}
