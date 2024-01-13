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

use Phpfastcache\Exceptions\PhpfastcacheExtensionNotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException;
use Phpfastcache\Helper\UninstanciableObjectTrait;

/**
 * @internal This extension manager is meant to manage officials Phpfastcache's extensions.
 * @see \Phpfastcache\CacheManager::addCustomDriver() to add you own drivers.
 */
final class ExtensionManager
{
    use UninstanciableObjectTrait;

    public const KNOWN_EXTENSION_NAMES = [
        'Arangodb',
        'Couchbasev4',
        'Couchdb',
        'Dynamodb',
        'Firestore',
        'Mongodb',
        'Ravendb',
        'Solr'
    ];

    /**
     * @var array<string, string>
     */
    protected static array $registeredExtensions = [];

    public static function registerExtension(string $extensionName, string $driverClassName): void
    {
        if (!str_starts_with($driverClassName, ltrim('Phpfastcache\\Extensions\\', '\\'))) {
            throw new PhpfastcacheInvalidArgumentException(
                'Only extensions from "\\Phpfastcache\\Extensions\\" namespace are allowed. Use CacheManager::addCustomDriver() to create your own extensions.'
            );
        }
        self::$registeredExtensions[$extensionName] = $driverClassName;
    }

    public static function extensionExists(string $extensionName): bool
    {
        return isset(self::$registeredExtensions[$extensionName]);
    }

    /**
     * @param string $name
     * @return string
     * @throws PhpfastcacheExtensionNotFoundException
     */
    public static function getExtension(string $name): string
    {
        if (isset(self::$registeredExtensions[$name])) {
            return self::$registeredExtensions[$name];
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
