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
 * Simple statements can be executed using a Session instance.
 * They are constructed with a CQL string that can contain positional
 * argument markers `?`.
 *
 * NOTE: Positional argument are only valid for native protocol v2+.
 *
 * @see Session::execute()
 */
final class SimpleStatement implements Statement
{
    /**
     * Creates a new simple statement with the provided CQL.
     *
     * @param string $cql CQL string for this simple statement
     */
    public function __construct($cql) {}
}
