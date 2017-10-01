<?php
/**
 * Created by PhpStorm.
 * User: Geolim4
 * Date: 01/10/2017
 * Time: 00:08
 */

namespace phpFastCache\Util;

/**
 * Class ArrayObject
 * @package phpFastCache\Util
 */
class ArrayObject implements \ArrayAccess, \Iterator, \Countable
{

    /**
     * @var array
     */
    protected $array = [];

    /**
     * @var int
     */
    protected $position = 0;

    /**
     * ArrayObject constructor.
     */
    public function __construct(...$args)
    {
        $this->position = 0;
        $this->array = (count($args) === 1 && is_array($args[0] ) ? $args[0] : $args);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->array[$this->position];
    }

    /**
     *
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->offsetExists($this->position);
    }

    /**
     *
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->array);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->array);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->array[ $offset ]) ? $this->array[ $offset ] : null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        // NOTE: THIS IS THE FIX FOR THE ISSUE "Indirect modification of overloaded element of SplFixedArray has no effect"
        // NOTE: WHEN APPENDING AN ARRAY (E.G. myArr[] = 5) THE KEY IS NULL, SO WE TEST FOR THIS CONDITION BELOW, AND VOILA

        if (is_null($offset))
        {
            $this->array[] = $value;
        }
        else
        {
            $this->array[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->array[ $offset ]);
    }

    /**
     * @return array|mixed
     */
    public function toArray()
    {
        return $this->array;
    }
}