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
 * A PHP representation of the CQL `timeuuid` datatype
 */
final class Timeuuid implements Value, UuidInterface
{
    /**
     * Creates a timeuuid from a given timestamp or current time.
     *
     * @param int $timestamp Unix timestamp
     */
    public function __construct($timestamp = null) {}

    /**
     * The type of this timeuuid.
     *
     * @return Type
     */
    public function type() {}

    /**
     * Returns this timeuuid as string.
     *
     * @return string timeuuid
     */
    public function __toString() {}

    /**
     * Returns this timeuuid as string.
     *
     * @return string timeuuid
     */
    public function uuid() {}

    /**
     * Returns the version of this timeuuid.
     *
     * @return int version of this timeuuid
     */
    public function version() {}

    /**
     * Unix timestamp.
     *
     * @return int seconds
     * @see time
     */
    public function time() {}

    /**
     * Converts current timeuuid to PHP DateTime.
     *
     * @return \DateTime PHP representation
     */
    public function toDateTime() {}
}
