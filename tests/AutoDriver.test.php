<?php

/**
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> https://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 */

use Phpfastcache\CacheManager;
use Phpfastcache\Core\Pool\ExtendedCacheItemPoolInterface;
use Phpfastcache\Exceptions\PhpfastcacheRootException;
use Phpfastcache\Helper\TestHelper;

chdir(__DIR__);
require_once __DIR__ . '/../vendor/autoload.php';
$testHelper = new TestHelper('"Auto" driver');

$testHelper->printSkipText('This feature has been removed as of phpfastcache V8');
/*try{
    $driverInstance = CacheManager::getInstance('Auto');
    if($driverInstance instanceof ExtendedCacheItemPoolInterface){
        $testHelper->printPassText(sprintf('Found "%s" driver in "Auto" context', get_class($driverInstance)));
    }else{
        $testHelper->printFailText('No driver found in "Auto" context');
    }

}catch (PhpfastcacheRootException $e){
    $testHelper->printFailText('Got an exception while trying to find a driver in "Auto" context: ' . $e->getMessage());
}
*/

$testHelper->terminateTest();
