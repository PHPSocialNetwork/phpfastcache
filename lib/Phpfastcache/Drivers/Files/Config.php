<?php
/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

declare(strict_types=1);

namespace Phpfastcache\Drivers\Files;

use Phpfastcache\Config\ConfigurationOption;
use Phpfastcache\Config\IOConfigurationOptionInterface;
use Phpfastcache\Config\IOConfigurationOptionTrait;

class Config extends ConfigurationOption implements IOConfigurationOptionInterface
{
    use IOConfigurationOptionTrait;
}
