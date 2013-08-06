<?php

/*
 * khoaofgod@yahoo.com
 * Website: http://www.phpfastcache.com
 * Example at our website, any bugs, problems, please visit http://www.codehelper.io
 */


interface phpfastcache_driver {
    /*
     * Check if this Cache driver is available for server or not
     */
     function __construct($option = array());

     function checkdriver();

    /*
     * check Existing cache
     * return true if exist and false if not
     */
     function isExisting($keyword);

    /*
     * SET
     * set a obj to cache
     */
     function set($keyword, $value = "", $time = 300, $option = array() );

    /*
     * GET
     * return null or value of cache
     */
     function get($keyword, $option = array());

    /*
     * Stats
     * Show stats of caching
     * Return array ("info","size","data")
     */
     function stats($option = array());

    /*
     * Delete
     * Delete a cache
     */
     function delete($keyword, $option = array());

    /*
     * clean
     * Clean up whole cache
     */
     function clean($option = array());

    /*
     * Increment
     */
     function increment($keyword, $step = 1, $option = array());

    /*
     * Decrement
     */

     function decrement($keyword, $step = 1,  $option = array());




}