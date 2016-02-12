<?php
namespace phpFastCache\Util;


/**
 * Class UnicodeUtil
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */
class UnicodeUtil
{
    public static function setEncoding($encoding = 'UTF-8', $language = null)
    {
        if ($language === null || !in_array($language, array('uni', 'Japanese', 'ja', 'English', 'en'), true)) {
            $language = 'uni';
        }
        switch(strtoupper($encoding))
        {
            case 'UTF-8':
                mb_internal_encoding($encoding);
                mb_http_output($encoding);
                mb_http_input($encoding);
                mb_language($language);
                mb_regex_encoding($encoding);
                break;
        }
    }
}