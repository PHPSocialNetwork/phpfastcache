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
 * A PHP representation of an index
 */
final class DefaultIndex implements Index
{
    /**
     * {@inheritDoc}
     *
     * @return string Name of the index
     */
    public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return string Kind of the index
     */
    public function kind() {}

    /**
     * {@inheritDoc}
     *
     * @return string Target column name of the index
     */
    public function target() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Value Value of an option by name
     */
    public function option($name) {}

    /**
     * {@inheritDoc}
     *
     * @return array A dictionary of `string` and `Cassandra\Value pairs of the
     *               index's options.
     */
    public function options() {}

    /**
     * {@inheritDoc}
     *
     * @return string Class name of a custom index
     */
    public function className() {}

    /**
     * {@inheritDoc}
     *
     * @return boolean
     */
    public function isCustom() {}
}
