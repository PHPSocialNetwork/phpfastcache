<?php

    /*
     * As reported, HOA Project cause some conflict problem with Autoload of Composer.
     * This is not phpFastCache problem, we used same autoload with Google Re-Capcha.
     * So, Until HOA, or the your project fixed the autoload.
     * All you need is put this line on your config.
     */

     // In your Setting / Config.php or Index.php
    define("PHPFASTCACHE_LEGACY",true);

    // If you use composer, then it auto included our "src/phpFastCache/phpFastCache.php" on vendor folder already, you don't need to do anything else

    require_once __DIR__.'/../src/autoload.php';

    // run Files Example
    require_once __DIR__.'/files.php';

    /*
     * It also bring back the __c() legacy function
     */