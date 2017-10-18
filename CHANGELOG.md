##7.0.0-alpha
##### 18 october 2017
- __Global__
  - Added php7 type hint support. Strict type support is coming in next alphas
  - Added changelog as requested by @rdecourtney in #517
  - Added `phpFastCacheInstanceNotFoundException` exception
- __Drivers__
  - Added Riak driver
  - Fixed wrong type hint returned by Predis
- __Cache Manager__
  - Added custom Instance ID feature as requested in #477
- __Helpers__
  - Modified ActOnAll helper behavior, this helper now returns an array of driver returns and does no longer implements `ExtendedCacheItemPoolInterface`

##7.0.0-dev
##### 01 october 2017
- Initialized v7 development