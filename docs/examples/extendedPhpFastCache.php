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

namespace MyCustom\Project;

use Phpfastcache\Drivers\Files\Driver as FilesDriver;
use Phpfastcache\Proxy\PhpfastcacheAbstractProxy;

/**
 * Specific driver-based example
 * Class extendsPhpFastCache
 * @package MyCustom\Project
 */
class extendedPhpFastCache extends FilesDriver
{
    public function __construct(array $config = [])
    {
        $config[ 'path' ] = 'your/custom/path/where/files/will/be/written';
        parent::__construct($config);
        /**
         * That's all !! Your cache class is ready to use
         */
    }
}


/**
 * Dynamic driver-based example
 * Class myCustomCacheClass
 * @package MyCustom\Project
 */
class myCustomCacheClass extends PhpfastcacheAbstractProxy
{
    public function __construct($driver = '', array $config = [])
    {
        $config[ 'path' ] = 'your/custom/path/where/files/will/be/written';
        $driver = 'files';
        parent::__construct($driver, $config);
        /**
         * That's all !! Your cache class is ready to use
         */
    }
}