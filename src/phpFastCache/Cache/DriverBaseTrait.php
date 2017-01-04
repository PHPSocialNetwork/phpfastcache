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
namespace phpFastCache\Cache;

use phpFastCache\Core\ExtendedCacheItemPoolTrait;
use phpFastCache\Exceptions\phpFastCacheDriverException;

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
     * @param $file
     * @return string
     * @throws \Exception
     */
    protected function readfile($file)
    {
        if (function_exists('file_get_contents')) {
            return file_get_contents($file);
        } else {
            $string = '';

            $file_handle = @fopen($file, 'r');
            if (!$file_handle) {
                throw new phpFastCacheDriverException("Can't Read File", 96);

            }
            while (!feof($file_handle)) {
                $line = fgets($file_handle);
                $string .= $line;
            }
            fclose($file_handle);

            return $string;
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
        if (PHP_SAPI === 'apache2handler') {
            return true;
        } else {
            if (strpos(PHP_SAPI, 'handler') !== false) {
                return true;
            }
        }

        return false;
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
     * @param \phpFastCache\Cache\ExtendedCacheItemInterface $item
     * @return array
     */
    public function driverPreWrap(ExtendedCacheItemInterface $item)
    {
        return [
          self::DRIVER_DATA_WRAPPER_INDEX => $item->get(),
          self::DRIVER_TIME_WRAPPER_INDEX => $item->getExpirationDate(),
          self::DRIVER_TAGS_WRAPPER_INDEX => $item->getTags(),
        ];
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
    public function driverUnwrapTime(array $wrapper)
    {
        return $wrapper[ self::DRIVER_TIME_WRAPPER_INDEX ];
    }

    /**
     * @return string
     */
    public function getDriverName()
    {
        static $driverName;

        return ($driverName ?: $driverName = ucfirst(substr(strrchr((new \ReflectionObject($this))->getNamespaceName(), '\\'), 1)));
    }

    /**
     * @param \phpFastCache\Cache\ExtendedCacheItemInterface $item
     * @return bool
     * @throws \LogicException
     */
    public function driverWriteTags(ExtendedCacheItemInterface $item)
    {
        /**
         * Do not attempt to write tags
         * on tags item, it can leads
         * to an infinite recursive calls
         */
        if(strpos($item->getKey(), self::DRIVER_TAGS_KEY_PREFIX ) === 0){
            throw new \LogicException('Trying to set tag(s) to an Tag item index: ' . $item->getKey());
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

            $tagsItem->set(array_merge((array) $data, [$item->getKey() => $expTimestamp]));

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
            $data = (array) $tagsItem->get();

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
                $tagsItem->expiresAt(max($data));
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
     * @return string
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
     * @throws \InvalidArgumentException
     */
    public static function isValidOption($optionName, $optionValue)
    {
        if (!is_string($optionName)) {
            throw new \InvalidArgumentException('$optionName must be a string');
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