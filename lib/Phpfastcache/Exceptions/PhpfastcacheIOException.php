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
 * @since v6
 */
class PhpfastcacheIOException extends PhpfastcacheCoreException
{
    /**
     * @inheritdoc
     */
    public function __construct(string $message = "", int $code = 0, \Throwable $previous = null)
    {
        $lastError = error_get_last();
        if ($lastError) {
            $message .= "\n";
            $message .= "Additional information provided by error_get_last():\n";
            $message .= "{$lastError['message']} in {$lastError['file']} line {$lastError['line']}";
        }
        parent::__construct($message, $code, $previous);
    }
}
