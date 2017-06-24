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
 * A PHP representation of the CQL `blob` datatype
 */
final class Blob implements Value
{
    /**
     * Creates a new bytes array.
     *
     * @param string $bytes any bytes
     */
    public function __construct($bytes) {}

    /**
     * The type of this blob.
     *
     * @return Type
     */
    public function type() {}

    /**
     * Returns bytes as a hex string.
     *
     * @return string bytes as hexadecimal string
     */
    public function bytes() {}

    /**
     * Returns bytes as a hex string.
     *
     * @return string bytes as hexadecimal string
     */
    public function __toString() {}

    /**
     * Returns bytes as a binary string.
     *
     * @return string bytes as binary string
     */
    public function toBinaryString() {}
}
