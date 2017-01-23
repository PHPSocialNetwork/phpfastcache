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

namespace Cassandra\Type;

use Cassandra\Type;

final class Set extends Type
{
    /**
     * Returns "set"
     * @return string "set"
     */
    public function name() {}

    /**
     * Returns type representation in CQL, e.g. `set<varchar>`
     * @return string Type representation in CQL
     */
    public function __toString() {}

    /**
     * Returns type of values
     * @return Type Type of values
     */
    public function valueType() {}

    /**
     * Creates a new Cassandra\Set from the given values.
     *
     * @throws Exception\InvalidArgumentException when values given are of a
     *                                            different type than what this
     *                                            set type expects.
     *
     * @param  mixed $value,... One or more values to be added to the set. When
     *                          no values are given, creates an empty set.
     * @return Cassandra\Set    A set with given values.
     */
    public function create($value = null) {}
}
