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

final class Custom extends Type
{
    /**
     * {@inheritDoc}
     *
     * @return string The name of this type
     */
    public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return string String representation of this type
     */
    public function __toString() {}

    /**
     * Creation of custom type instances is not supported
     *
     * @throws Cassandra\Exception\LogicException
     *
     * @param  mixed $value the value
     * @return null         nothing
     */
    public function create($value = null) {}
}
