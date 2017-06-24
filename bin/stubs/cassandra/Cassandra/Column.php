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
 * A PHP representation of a column
 */
interface Column
{
    /**
     * Returns the name of the column.
     * @return string Name of the column or null
     */
    function name();

    /**
     * Returns the type of the column.
     * @return Cassandra\Type Type of the column
     */
    function type();

    /**
     * Returns whether the column is in descending or ascending order.
     * @return boolean Whether the column is stored in descending order.
     */
    function isReversed();

    /**
     * Returns true for static columns.
     * @return boolean Whether the column is static
     */
    function isStatic();

    /**
     * Returns true for frozen columns.
     * @return boolean Whether the column is frozen
     */
    function isFrozen();

    /**
     * Returns name of the index if defined.
     * @return string Name of the index if defined or null
     */
    function indexName();

    /**
     * Returns index options if present.
     * @return string Index options if present or null
     */
    function indexOptions();
}
