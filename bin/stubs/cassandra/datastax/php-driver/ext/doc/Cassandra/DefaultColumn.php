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
final class DefaultColumn implements Column
{
    /**
     * {@inheritDoc}
     *
     * @return string Name of the column or null
     */
    public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Type Type of the column
     */
    public function type() {}

    /**
     * {@inheritDoc}
     *
     * @return boolean Whether the column is stored in descending order.
     */
    public function isReversed() {}

    /**
     * {@inheritDoc}
     *
     * @return boolean Whether the column is static
     */
    public function isStatic() {}

    /**
     * {@inheritDoc}
     *
     * @return boolean Whether the column is frozen
     */
    public function isFrozen() {}

    /**
     * {@inheritDoc}
     *
     * @return string Name of the index if defined or null
     */
    public function indexName() {}

    /**
     * {@inheritDoc}
     *
     * @return string Index options if present or null
     */
    public function indexOptions() {}
}
