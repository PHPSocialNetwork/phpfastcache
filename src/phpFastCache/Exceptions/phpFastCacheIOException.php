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
 * Class phpFastCacheIOException
 * @package phpFastCache\Exceptions
 * @since v6
 */
class phpFastCacheIOException extends phpFastCacheCoreException
{
    /**
     * @inheritdoc
     */
    public function __construct($message = "", $code = 0, $previous = null)
    {
        $lastError = error_get_last();
        if($lastError){
            $message .= "\n";
            $message .= "Additional information provided by error_get_last():\n";
            $message .= "{$lastError['message']} in {$lastError['file']} line {$lastError['line']}";
        }
        parent::__construct($message, $code, $previous);
    }
}