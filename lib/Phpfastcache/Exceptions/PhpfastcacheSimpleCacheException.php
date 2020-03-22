<?php

/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
declare(strict_types=1);

namespace Phpfastcache\Exceptions;

use Exception;
use Psr\SimpleCache\CacheException;

/**
 * Class PhpfastcacheRootException
 * @package Phpfastcache\Exceptions
 */
class PhpfastcacheSimpleCacheException extends Exception implements CacheException
{

}