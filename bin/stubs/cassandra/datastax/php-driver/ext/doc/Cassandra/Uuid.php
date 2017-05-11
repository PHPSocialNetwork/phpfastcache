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
 * A PHP representation of the CQL `uuid` datatype
 */
final class Uuid implements Value, UuidInterface
{
    /**
     * Creates a uuid from a given uuid string or a random one.
     *
     * @param string $uuid A uuid string
     */
    public function __construct($uuid = null) {}

    /**
     * Returns this uuid as string.
     *
     * @return string uuid
     */
    public function __toString() {}

    /**
     * The type of this uuid.
     *
     * @return Type
     */
    public function type() {}

    /**
     * Returns this uuid as string.
     *
     * @return string uuid
     */
    public function uuid() {}

    /**
     * Returns the version of this uuid.
     *
     * @return int version of this uuid
     */
    public function version() {}
}
