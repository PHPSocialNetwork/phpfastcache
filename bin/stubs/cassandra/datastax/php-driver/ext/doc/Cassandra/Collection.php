<?php

/**
 * Copyright 2015-2016 DataStax, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Cassandra;

/**
 * A PHP representation of the CQL `list` datatype
 */
final class Collection implements Value, \Countable, \Iterator
{
    /**
     * Creates a new collection of a given type.
     *
     * @param Type $type
     */
    public function __construct($type) {}

    /**
     * The type of this collection.
     *
     * @return Type
     */
    public function type() {}

    /**
     * Array of values in this collection.
     *
     * @return array values
     */
    public function values() {}

    /**
     * Adds one or more values to this collection.
     *
     * @param mixed $value,... one or more values to add
     *
     * @return int total number of values in this collection
     */
    public function add($value) {}

    /**
     * Deletes the value at a given index
     *
     * @param int $index   Index
     *
     * @return bool        Whether the value at a given index is correctly removed
     */
    public function remove($index) {}

    /**
     * Retrieves the value at a given index.
     *
     * @param   int   $index  Index
     * @return  mixed         Value or null
     */
    public function get($index) {}

    /**
     * Finds index of a value in this collection.
     *
     * @param   mixed  $value  Value
     * @return  int            Index or null
     */
    public function find($value) {}

    /**
     * Total number of elements in this collection
     *
     * @return int count
     */
    public function count() {}

    /**
     * Current element for iteration
     *
     * @return mixed current element
     */
    public function current() {}

    /**
     * Current key for iteration
     *
     * @return int current key
     */
    public function key() {}

    /**
     * Move internal iterator forward
     *
     * @return void
     */
    public function next() {}

    /**
     * Check whether a current value exists
     *
     * @return bool
     */
    public function valid() {}

    /**
     * Rewind internal iterator
     *
     * @return void
     */
    public function rewind() {}
}
