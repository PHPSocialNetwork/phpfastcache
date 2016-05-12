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

namespace phpFastCache\Core;

use InvalidArgumentException;
use phpFastCache\Cache\ExtendedCacheItemInterface;
use phpFastCache\Cache\ExtendedCacheItemPoolInterface;
use phpFastCache\Exceptions\phpFastCacheDriverException;
use phpFastCache\CacheManager;
use Psr\Cache\CacheItemInterface;

/**
 * Class DriverAbstract
 * @package phpFastCache\Core
 */
abstract class DriverAbstract implements ExtendedCacheItemPoolInterface
{
    const DRIVER_CHECK_FAILURE      = '%s is not installed or misconfigured, cannot continue.';
    const DRIVER_TAGS_KEY_PREFIX    = '_TAG_';
    const DRIVER_DATA_WRAPPER_INDEX = 'd';
    const DRIVER_TIME_WRAPPER_INDEX = 't';
    const DRIVER_TAGS_WRAPPER_INDEX = 'g';

    /**
     * @var array
     */
    public $extension_dir = '_extensions';

    /**
     * @var array
     */
    public $tmp = [];

    /**
     * @var array default options, this will be merge to Driver's Options
     */
    public $config = [];

    /**
     * @var bool
     */
    public $fallback = false;

    /**
     * @var mixed Instance of driver service
     */
    public $instance;


    public function __destruct()
    {
        // clean up the memory and don't want for PHP clean for caching method "phpfastcache"
        if (isset($this->config[ 'instance' ]) && (int) $this->config[ 'cache_method' ] === 3) {
            CacheManager::cleanCachingMethod($this->config[ 'instance' ]);
        }
    }

    /**
     * @param $keyword
     * @return string
     */
    protected function encodeFilename($keyword)
    {
        // return trim(trim(preg_replace('/[^a-zA-Z0-9]+/', '_', $keyword), '_'));
        // return rtrim(base64_encode($keyword), '=');
        return md5($keyword);
    }

    /**
     * @param $keyword
     * @return bool
     */
    public function isExisting($keyword)
    {
        if (method_exists($this, 'driver_isExisting')) {
            return $this->driver_isExisting($keyword);
        }

        $data = $this->get($keyword);
        if ($data == null) {
            return false;
        } else {
            return true;
        }

    }

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
        $x = @unserialize($value);
        if ($x == false) {
            return $value;
        } else {
            return $x;
        }
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
     */
    public function driverWriteTags(ExtendedCacheItemInterface $item)
    {
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
            } else {
                $this->driverDelete($tagsItem);
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
     * @param string $tagName
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $driverResponse = $this->driverRead($this->getTagKey($tagName));
            if ($driverResponse) {
                $items = (array) $this->driverUnwrapData($driverResponse);

                return $this->getItems(array_unique(array_keys($items)));
            } else {
                return [];
            }
        } else {
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @param array $tagNames
     * @return \phpFastCache\Cache\ExtendedCacheItemInterface[]
     * @throws InvalidArgumentException
     */
    public function getItemsByTags(array $tagNames)
    {
        $items = [];
        foreach (array_unique($tagNames) as $tagName) {
            $items = array_merge($items, $this->getItemsByTag($tagName));
        }

        return $items;
    }

    /**
     * @param string $tagName
     * @return bool|null
     * @throws InvalidArgumentException
     */
    public function deleteItemsByTag($tagName)
    {
        if (is_string($tagName)) {
            $return = null;
            foreach ($this->getItemsByTag($tagName) as $item) {
                $result = $this->driverDelete($item);
                if ($return !== false) {
                    $return = $result;
                }
            }

            return $return;
        } else {
            throw new InvalidArgumentException('$tagName must be a string');
        }
    }

    /**
     * @param array $tagNames
     * @return bool|null
     * @throws InvalidArgumentException
     */
    public function deleteItemsByTags(array $tagNames)
    {
        $return = null;
        foreach ($tagNames as $tagName) {
            $result = $this->deleteItemsByTag($tagName);
            if ($return !== false) {
                $return = $result;
            }
        }

        return $return;
    }

    /**
     * Abstract Drivers Methods
     */

    /**
     * @param string $key
     * @return array [
     *      'd' => 'THE ITEM DATA'
     *      't' => 'THE ITEM DATE EXPIRATION'
     *      'g' => 'THE ITEM TAGS'
     * ]
     *
     */
    abstract public function driverRead($key);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return mixed
     */
    abstract public function driverWrite(CacheItemInterface $item);

    /**
     * @return bool
     */
    abstract public function driverClear();

    /**
     * @return bool
     */
    abstract public function driverConnect();

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract public function driverDelete(CacheItemInterface $item);

    /**
     * @param \Psr\Cache\CacheItemInterface $item
     * @return bool
     */
    abstract public function driverIsHit(CacheItemInterface $item);

}