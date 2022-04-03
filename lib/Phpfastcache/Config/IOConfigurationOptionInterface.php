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

namespace Phpfastcache\Config;

interface IOConfigurationOptionInterface extends ConfigurationOptionInterface
{
    /**
     * Feel free to propose your own one
     * by opening a pull request :)
     */
    public const SAFE_FILE_EXTENSIONS = 'txt|cache|db|pfc';

    public function getSecurityKey(): string;

    public function setSecurityKey(string $securityKey): static;

    public function isSecureFileManipulation(): bool;

    public function setSecureFileManipulation(bool $secureFileManipulation): static;

    public function getCacheFileExtension(): string;

    public function setCacheFileExtension(string $cacheFileExtension): static;

    public function getDefaultChmod(): int;

    public function setDefaultChmod(int $defaultChmod): static;
}
