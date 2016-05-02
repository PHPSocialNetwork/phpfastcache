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
namespace phpFastCache\Entities;

use ArrayAccess;
use InvalidArgumentException;
use LogicException;

/**
 * Class driverStatistic
 * @package phpFastCache\Entities
 */
class driverStatistic implements ArrayAccess
{
    /**
     * @var string
     */
    protected $info = '';

    /**
     * @var string
     */
    protected $size = '';

    /**
     * @var string
     */
    protected $data = '';

    /**
     * @return string or false if no information available
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string or false if no size available
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string or false if no data available
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param $info
     * @return $this
     */
    public function setInfo($info)
    {
        $this->info = ($info ?: false);
        return $this;
    }


    /**
     * @param $size
     * @return $this
     */
    public function setSize($size)
    {
        $this->size = ($size ?: false);
        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = ($data ?: false);
        return $this;
    }

    /*****************
     * ArrayAccess
     *****************/

    /**
     * @param string $offset
     * @param string $value
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function offsetSet($offset, $value) {
        trigger_error($this->getDeprecatedMsg(), E_USER_DEPRECATED);
        if (!is_string($offset)) {
            throw new InvalidArgumentException('$offset must be a string');
        } else {
            if(property_exists($this, $offset)){
                $this->{$offset} = $value;
            }else{
                throw new LogicException("Property {$offset} does not exists");
            }
        }
    }

    /**
     * @param string $offset
     * @return bool
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function offsetExists($offset) {
        trigger_error($this->getDeprecatedMsg(), E_USER_DEPRECATED);
        if (!is_string($offset)) {
            throw new InvalidArgumentException('$offset must be a string');
        } else {
            if(property_exists($this, $offset)){
                return isset($this->{$offset});
            }else{
                throw new LogicException("Property {$offset} does not exists");
            }
        }
    }

    /**
     * @param string $offset
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function offsetUnset($offset) {
        trigger_error($this->getDeprecatedMsg(), E_USER_DEPRECATED);
        if (!is_string($offset)) {
            throw new InvalidArgumentException('$offset must be a string');
        } else {
            if(property_exists($this, $offset)){
                unset($this->{$offset});
            }else{
                throw new LogicException("Property {$offset} does not exists");
            }
        }
    }

    /**
     * @param string $offset
     * @return string
     * @throws InvalidArgumentException
     * @throws LogicException
     */
    public function offsetGet($offset) {
        trigger_error($this->getDeprecatedMsg(), E_USER_DEPRECATED);
        if (!is_string($offset)) {
            throw new InvalidArgumentException('$offset must be a string');
        } else {
            if(property_exists($this, $offset)){
                return isset($this->{$offset}) ? $this->{$offset} : null;
            }else{
                throw new LogicException("Property {$offset} does not exists");
            }
        }
    }

    /**
     * @return string
     */
    private function getDeprecatedMsg()
    {
        return 'You should consider upgrading your code and treat the statistic array as an object. 
        The arrayAccess compatibility will be removed in the next major release';
    }
}