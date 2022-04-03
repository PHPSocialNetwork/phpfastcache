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

\class_alias(ConfigurationOption::class, \Phpfastcache\Config\Config::class);
\trigger_error(
    \sprintf(
        '%s class is deprecated and will be removed in v10, use %s instead.',
        \Phpfastcache\Config\Config::class,
        ConfigurationOption::class
    ),
    \E_USER_DEPRECATED
);
