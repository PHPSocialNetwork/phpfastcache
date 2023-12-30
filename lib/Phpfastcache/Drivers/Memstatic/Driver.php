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

namespace Phpfastcache\Drivers\Memstatic;

use Phpfastcache\Config\ConfigurationOptionInterface;
use Phpfastcache\Event\EventManagerInterface;

/**
 * @deprecated Memstatic driver has changed its name, it is now called "Memory".
 * @see \Phpfastcache\Drivers\Memory\Driver
 */
class Driver extends \Phpfastcache\Drivers\Memory\Driver
{
    public function __construct(#[\SensitiveParameter] ConfigurationOptionInterface $config, string $instanceId, EventManagerInterface $em)
    {
        trigger_error('Memstatic driver has changed its name, it is now called "Memory"', E_USER_DEPRECATED);
        parent::__construct($config, $instanceId, $em);
    }
}
