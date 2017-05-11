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
 * A PHP representation of the CQL `inet` datatype
 */
final class Inet implements Value
{
    /**
     * Creates a new IPv4 or IPv6 inet address.
     *
     * @param string $address any IPv4 or IPv6 address
     */
    public function __construct($address) {}

    /**
     * The type of this inet.
     *
     * @return Type
     */
    public function type() {}

    /**
     * Returns the normalized string representation of the address.
     *
     * @return string address
     */
    public function address() {}

    /**
     * Returns the normalized string representation of the address.
     *
     * @return string address
     */
    public function __toString() {}
}
