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

use Phpfastcache\Config\ConfigurationOption;

/**
 * @deprecated will be removed as of v10 due to the lack of updates to PHP8 as officially stated by PHP: https://www.php.net/manual/en/install.windows.recommended.php
 */
class Config extends ConfigurationOption
{
    public function __construct(array $parameters = [])
    {
        trigger_error(
            'Wincache is now deprecated and will be removed as of v10 due to the lack of updates to PHP8 as officially stated by PHP:
            https://www.php.net/manual/en/install.windows.recommended.php',
            E_USER_DEPRECATED
        );
        parent::__construct($parameters);
    }
}
