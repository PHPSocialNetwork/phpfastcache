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
 * A PHP representation of the CQL `date` type.
 */
final class Date implements Value
{
    /**
     * Creates a new Date object
     *
     * @param int $seconds Absolute seconds from epoch (1970, 1, 1), can be negative, defaults to current time
     */
    public function __construct($seconds = null) {}

    /**
     * The type of this date.
     *
     * @return Type
     */
    public function type() {}

    /**
     * @return int Absolute seconds from epoch (1970, 1, 1), can be negative
     */
    public function seconds() {}

    /**
     * Converts current date to PHP DateTime.
     *
     * @return \DateTime PHP representation
     */
    public function toDateTime() {}

    /**
     * @return string this date in string format: Cassandra\Date(seconds=$seconds)
     */
    public function __toString() {}
}
