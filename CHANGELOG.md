## 10.0.0
##### xx xxxxxxx xxxx
- __Core__
  - Removed deprecated methods`ConfigurationOption::isPreventCacheSlams()`, `ConfigurationOption::setPreventCacheSlams()`, `ConfigurationOption::getCacheSlamsTimeout()`, `ConfigurationOption::setCacheSlamsTimeout()`. ([See changes](CHANGELOG_API.md)).
    Removed deprecated class `\Phpfastcache\Config\Config::class`.
- __Helpers__
  - Removed `\Phpfastcache\Helper\CacheConditionalHelper`. Use `\Phpfastcache\CacheContract` instead.
- __Drivers__
  - Removed deprecated method `\Phpfastcache\Entities\DriverStatistic::getData()`.
  - Removed deprecated method  `\Phpfastcache\Entities\DriverStatistic::setData()`.
- __Events__
  - Phpfastcache EventManager is now [PSR-14](https://www.php-fig.org/psr/psr-14/) compliant. Therefore, its API has slightly changed and may not be backward-compatible.
  - Removed deprecated method `\Phpfastcache\Event\EventManagerDispatcherInterface::hasEventManager`.
- __Drivers__
  - Removed driver `Memstatic`. Use `Memory` instead.
  - Removed driver `Wincache`.
