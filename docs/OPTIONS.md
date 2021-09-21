Phpfastcache has some options that you may want to know before using them.

### Global options
The global options are defined in `\Phpfastcache\Config\ConfigurationOption` ([ConfigurationOption.php](../lib/Phpfastcache/Config/ConfigurationOption.php))

### Driver-specific options *
The driver-specific options are defined in `Config.php` file located in each driver [directory](../lib/Phpfastcache/Drivers).
Each driver-specific `Config` class file extends `\Phpfastcache\Config\ConfigurationOption`
