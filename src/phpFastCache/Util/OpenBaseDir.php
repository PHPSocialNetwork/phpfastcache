<?php
namespace phpFastCache\Util;
define('PHP_OPEN_BASEDIR', @ini_get("open_basedir"));

class OpenBaseDir {
    public static $stores = array();
    public static function checkBaseDir($path) {
        if(!is_null(PHP_OPEN_BASEDIR) && PHP_OPEN_BASEDIR != "") {
            /*
             * ONLY check ONE time if System Have Open Base Dir
             * Else, always return TRUE for system without OPenBaseDir
             */
            $index = md5($path);
            if (!isset(self::$stores[$index])) {
                // never check before, then check it 1 one time for the src dir only
                $list = explode(":", PHP_OPEN_BASEDIR);
                foreach ($list as $allowed_path) {
                    $tmp = explode($allowed_path, $path, 2);
                    if ($tmp[0] != $path) {
                        // echo "<br>".$tmp[0]." = ".$path." BY {$allowed_path}";
                        self::$stores[$index] = true;
                        return true;
                    }
                }
                self::$stores[$index] = false;
            } else {
                return self::$stores[$index];
            }
            return false;
        }
        return true;
    }
}