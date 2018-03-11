<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Exceptions;

use Psr\Cache\CacheException;

/**
 * Class PhpfastcacheRootException
 * @package Phpfastcache\Exceptions
 */
class PhpfastcacheRootException extends \Exception implements CacheException
{

}