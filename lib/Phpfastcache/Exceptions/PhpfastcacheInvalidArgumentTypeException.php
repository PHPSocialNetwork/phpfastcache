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

namespace Phpfastcache\Exceptions;


/**
 * Class PhpfastcacheInvalidArgumentTypeException
 * @package Phpfastcache\Exceptions
 */
class PhpfastcacheInvalidArgumentTypeException extends PhpfastcacheInvalidArgumentException
{
    /**
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $expectedType
     * @param mixed $unexpectedData
     */
    public function __construct($expectedType, $unexpectedData)
    {
        $type = gettype($unexpectedData);
        parent::__construct("Expecting '{$expectedType}', got '" . ($type === 'object' ? $type . '(' . get_class($type) . ')' : $type) . "'");
    }
}
