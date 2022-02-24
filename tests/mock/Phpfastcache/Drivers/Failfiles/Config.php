<?php

declare(strict_types=1);
/**
 * This file is part of Phpfastcache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt and LICENCE files.
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 * @author Contributors  https://github.com/PHPSocialNetwork/phpfastcache/graphs/contributors
 */

namespace Phpfastcache\Drivers\Failfiles;

use Phpfastcache\Drivers\Files\Config as FilesConfig;

/**
 * Class Config
 */
class Config extends FilesConfig
{
    /**
     * @var bool
     */
    protected $customOption = true;

    public function isCustomOption(): bool
    {
        return $this->customOption;
    }

    public function setCustomOption(bool $customOption): self
    {
        $this->customOption = $customOption;

        return $this;
    }
}
