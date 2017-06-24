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
 * A PHP representation of the CQL `time` type.
 */
final class Time implements Value
{
    /**
     * Creates a new Time object
     *
     * @param int|string $nanoseconds  Number of nanoseconds since last microsecond
     */
    public function __construct($nanoseconds = 0) {}


    /**
     * Creates a new Time object with the current time.
     *
     * @return Time
     */
    public static function now() {}

    /**
     * The type of this date.
     *
     * @return Type
     */
    public function type() {}

    /**
     * @return int number of nanoseconds since the last full microsecond since
     *             the last full second since the last full minute since the
     *             last full hour since midnight
     */
    public function nanoseconds() {}

    /**
     * @return string this date in string format: Cassandra\Time(nanoseconds=$nanoseconds)
     */
    public function __toString() {}
}
