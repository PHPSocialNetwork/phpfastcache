<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 12/02/2018
 * Time: 23:10
 */

namespace Phpfastcache\Drivers\Sqlite;

use Phpfastcache\Config\{
    ConfigurationOption, IOConfigurationOptionTrait
};

class Config extends ConfigurationOption
{
    use IOConfigurationOptionTrait;
}