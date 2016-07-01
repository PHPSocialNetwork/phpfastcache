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

namespace phpFastCache\Util;

use phpFastCache\Exceptions\phpFastCacheCoreException;


/**
 * Class Languages
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
class Languages
{
    public static function setEncoding($encoding = 'UTF-8', $language = null)
    {
        if ($language === null || !in_array($language, ['uni', 'Japanese', 'ja', 'English', 'en'], true)) {
            $language = 'uni';
        }
        switch (strtoupper($encoding)) {
            case 'UTF-8':
                if (extension_loaded("mbstring")) {
                    mb_internal_encoding($encoding);
                    mb_http_output($encoding);
                    mb_http_input($encoding);
                    mb_language($language);
                    mb_regex_encoding($encoding);
                } else {
                    throw new phpFastCacheCoreException("MB String need to be installed for Unicode Encoding");
                }
                break;
        }
    }
}