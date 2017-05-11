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

final class UserType extends Type
{
    /**
     * Returns type name for the user type
     * @return string
     */
    public function name() {}

    /**
     * Returns keyspace for the user type
     * @return string
     */
    public function keyspace() {}

    /**
     * Returns type representation in CQL, e.g. 1keyspace.type_name1 or
     * `userType<name1:varchar, name2:int>`.
     *
     * @return string Type representation in CQL
     */
    public function __toString() {}

    /**
     * Returns types of values
     * @return array An array of types
     */
    public function types() {}

    /**
     * Creates a new Cassandra\UserTypeValue from the given name/value pairs.
     *
     * @throws Exception\InvalidArgumentException when values given are of a
     *                                            different types than what the
     *                                            user type expects.
     *
     * @param  mixed $value,...      One or more name/value pairs to be added to the user type.
     *                               When no values given, creates an empty user type.
     * @return Cassandra\UserTypeValue  A user type value with given name/value pairs.
     */
    public function create($value = null) {}
}
