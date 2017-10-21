<?php
/**
 *
 * This file is part of phpFastCache.
 *
 * @license MIT License (MIT)
 *
 * For full copyright and license information, please see the docs/CREDITS.txt file.
 *
 * @author Khoa Bui (khoaofgod)  <khoaofgod@gmail.com> http://www.phpfastcache.com
 * @author Georges.L (Geolim4)  <contact@geolim4.com>
 *
 */

namespace phpFastCache\Core\Pool;

use phpFastCache\Core\Item\ExtendedCacheItemInterface;
use phpFastCache\Exceptions\phpFastCacheInvalidArgumentException;
use phpFastCache\Exceptions\phpFastCacheLogicException;


/**
 * Class DriverBaseTrait
 * @package phpFastCache\Cache
 */
trait DriverBaseTrait
{
    use ExtendedCacheItemPoolTrait;

    /**
     * @var array default options, this will be merge to Driver's Options
     */
    protected $config = [];

    /**
     * @var bool
     */
    protected $fallback = false;

    /**
     * @var mixed Instance of driver service
     */
    protected $instance;

    /**
     * @var string
     */
    protected $driverName;

    /**
     * @param $config_name
     * @param string $value
     */
    public function setup($config_name, $value = '')
    {
        /**
         * Config for class
         */
        if (is_array($config_name)) {
            $this->config = array_merge($this->config, $config_name);
        } else {
            $this->config[ $config_name ] = $value;
        }
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }


    /**
     * @return mixed
     */
    public function getConfigOption($optionName)
    {
        if (isset($this->config[ $optionName ])) {
            return $this->config[ $optionName ];
        } else {
            return null;
        }
    }

    /**
     * Encode data types such as object/array
     * for driver that does not support
     * non-scalar value
     * @param $data
     * @return string
     */
    protected function encode($data)
    {
        return serialize($data);
    }

    /**
     * Decode data types such as object/array
     * for driver that does not support
     * non-scalar value
     * @param $value
     * @return mixed
     */
    protected function decode($value)
    {
        return @unserialize($value);
    }

    /**
     * Check phpModules or CGI
     * @return bool
     */
    protected function isPHPModule()
    {
        return (PHP_SAPI === 'apache2handler' || strpos(PHP_SAPI, 'handler') !== false);
    }


    /**
     * @param $class
     * @return bool
     */
    protected function isExistingDriver($class)
    {
        return class_exists("\\phpFastCache\\Drivers\\{$class}");
    }


    /**
     * @param $tag
     * @return string
     */
    protected function _getTagName($tag)
    {
        return "__tag__" . $tag;
    }

    /**
     * @param \phpFastCache\Core\Item\ExtendedCacheItemInterface $item
     * @return array
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item)
    {
        $wrap = [
          self::DRIVER_DATA_WRAPPER_INDEX => $item->get(),
          self::DRIVER_TAGS_WRAPPER_INDEX => $item->getTags(),
          self::DRIVER_EDATE_WRAPPER_INDEX => $item->getExpirationDate(),
        ];

        if ($this->config[ 'itemDetailedDate' ]) {
            $wrap[ self::DRIVER_MDATE_WRAPPER_INDEX ] = new \DateTime();
            /**
             * If the creation date exists
             * reuse it else set a new Date
             */
            $wrap[ self::DRIVER_CDATE_WRAPPER_INDEX ] = $item->getCreationDate() ?: new \DateTime();
        } else {
            $wrap[ self::DRIVER_MDATE_WRAPPER_INDEX ] = null;
            $wrap[ self::DRIVER_CDATE_WRAPPER_INDEX ] = null;
        }

        return $wrap;
    }

    /**
     * @param array $wrapper
     * @return mixed
     */
    public function driverUnwrapData(array $wrapper)
    {
        return $wrapper[ self::DRIVER_DATA_WRAPPER_INDEX ];
    }

    /**
     * @param array $wrapper
     * @return mixed
     */
    public function driverUnwrapTags(array $wrapper)
    {
        return $wrapper[ self::DRIVER_TAGS_WRAPPER_INDEX ];
    }


    /**
     * @param array $wrapper
     * @return \DateTime
     */
    public function driverUnwrapEdate(array $wrapper)
    {
        return $wrapper[ self::DRIVER_EDATE_WRAPPER_INDEX ];
    }

    /**
     * @param array $wrapper
     * @return \DateTime
     */
    public function driverUnwrapCdate(array $wrapper)
    {
        return $wrapper[ self::DRIVER_CDATE_WRAPPER_INDEX ];
    }


    /**
     * @param array $wrapper
     * @return \DateTime
     */
    public function driverUnwrapMdate(array $wrapper)
    {
        return $wrapper[ self::DRIVER_MDATE_WRAPPER_INDEX ];
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        if(!$this->driverName){
            $this->driverName = ucfirst(substr(strrchr((new \ReflectionObject($this))->getNamespaceName(), '\\'), 1));
        }
        return $this->driverName;
    }

    /**
     * @param \phpFastCache\Core\Item\ExtendedCacheItemInterface $item
     * @return bool
     * @throws phpFastCacheLogicException
     */
    public function driverWriteTags(ExtendedCacheItemInterface $item)
    {
        /**
         * Do not attempt to write tags
         * on tags item, it can leads
         * to an infinite recursive calls
         */
        if (strpos($item->getKey(), self::DRIVER_TAGS_KEY_PREFIX) === 0) {
            throw new phpFastCacheLogicException('Trying to set tag(s) to an Tag item index: ' . $item->getKey());
        }

        if(!$item->getTags() && !$item->getRemovedTags())
        {
            return true;
        }

        /**
         * @var $tagsItems ExtendedCacheItemInterface[]
         */
        $tagsItems = $this->getItems($this->getTagKeys($item->getTags()));

        foreach ($tagsItems as $tagsItem) {
            $data = $tagsItem->get();
            $expTimestamp = $item->getExpirationDate()->getTimestamp();

            /**
             * Using the key will
             * avoid to use array_unique
             * that has slow performances
             */

            $tagsItem->set(array_merge((array)$data, [$item->getKey() => $expTimestamp]));

            /**
             * Set the expiration date
             * of the $tagsItem based
             * on the older $item
             * expiration date
             */
            if ($expTimestamp > $tagsItem->getExpirationDate()->getTimestamp()) {
                $tagsItem->expiresAt($item->getExpirationDate());
            }
            $this->driverWrite($tagsItem);
            $tagsItem->setHit(true);
        }

        /**
         * Also update removed tags to
         * keep the index up to date
         */
        $tagsItems = $this->getItems($this->getTagKeys($item->getRemovedTags()));

        foreach ($tagsItems as $tagsItem) {
            $data = (array)$tagsItem->get();

            unset($data[ $item->getKey() ]);
            $tagsItem->set($data);

            /**
             * Recalculate the expiration date
             *
             * If the $tagsItem does not have
             * any cache item references left
             * then remove it from tagsItems index
             */
            if (count($data)) {
                $tagsItem->expiresAt((new \DateTime())->setTimestamp(max($data)));
                $this->driverWrite($tagsItem);
                $tagsItem->setHit(true);
            } else {
                $this->deleteItem($tagsItem->getKey());
            }
        }

        return true;
    }

    /**
     * @param $key
     * @return string
     */
    public function getTagKey($key)
    {
        return self::DRIVER_TAGS_KEY_PREFIX . $key;
    }

    /**
     * @param $key
     * @return array
     */
    public function getTagKeys(array $keys)
    {
        foreach ($keys as &$key) {
            $key = $this->getTagKey($key);
        }

        return $keys;
    }

    /**
     * @param string $optionName
     * @param mixed $optionValue
     * @return bool
     * @throws phpFastCacheInvalidArgumentException
     */
    public static function isValidOption($optionName, $optionValue)
    {
        if (!is_string($optionName)) {
            throw new phpFastCacheInvalidArgumentException('$optionName must be a string');
        }

        return true;
    }

    /**
     * @return array
     */
    public static function getRequiredOptions()
    {
        return [];
    }

    /**
     * @return array
     */
    public static function getValidOptions()
    {
        return [];
    }
}