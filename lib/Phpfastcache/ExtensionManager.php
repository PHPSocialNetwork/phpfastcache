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

namespace Phpfastcache;

use Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheExtensionNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheLogicException;
use Phpfastcache\Exceptions\PhpfastcacheUnsupportedOperationException;
use Phpfastcache\Helper\UninstanciableObjectTrait;

final class ExtensionManager
{
    use UninstanciableObjectTrait;

    /**
     * @var array<string, string>
     */
    protected static array $registeredExtensions = [];

    public static function registerExtension(string $extensionName, string $driverClassName): void
    {
        self::$registeredExtensions[$extensionName] = $driverClassName;
    }

    /**
     * Autoload all the discoverable extensions.
     *
     * @return void
     * @throws PhpfastcacheExtensionNotFoundException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheUnsupportedOperationException
     */
    public static function autoloadExtensions(): void
    {
        foreach (self::$registeredExtensions as $extension) {
            self::loadExtension($extension);
        }
    }

    /**
     * @param string $name
     * @return void
     * @throws PhpfastcacheExtensionNotFoundException
     * @throws PhpfastcacheInvalidArgumentException
     * @throws PhpfastcacheLogicException
     * @throws PhpfastcacheUnsupportedOperationException
     */
    public static function loadExtension(string $name): void
    {
        if (!CacheManager::customDriverExists($name)) {
            if (isset(self::$registeredExtensions[$name])) {
                CacheManager::addCustomDriver($name, self::$registeredExtensions[$name]);
            } else {
                throw new PhpfastcacheExtensionNotFoundException(
                    sprintf(
                        'Unable too find the %s extension. Make sure that you you added through composer: `composer require phpfastcache/%s-extension`',
                        $name,
                        strtolower($name)
                    )
                );
            }
        }
    }
}
