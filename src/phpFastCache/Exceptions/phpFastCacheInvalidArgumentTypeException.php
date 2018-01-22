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

namespace phpFastCache\Exceptions;

/**
 * Class phpFastCacheInvalidArgumentTypeException
 * @package phpFastCache\Exceptions
 */
class phpFastCacheInvalidArgumentTypeException extends phpFastCacheInvalidArgumentException
{
    /**
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $expectedType
     * @param mixed $unexpectedData
     */
    public function __construct($expectedType, $unexpectedData)
    {
        $type = \gettype($unexpectedData);
        parent::__construct("Expecting '{$expectedType}', got '" . ($type === 'object' ? $type . '(' . \get_class($type) . ')' : $type) . "'");
    }
}