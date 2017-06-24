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
 * A PHP representation of a keyspace
 */
interface Keyspace
{
    /**
     * Returns keyspace name
     * @return string Name
     */
    function name();

    /**
     * Returns replication class name
     * @return string Replication class
     */
    function replicationClassName();

    /**
     * Returns replication options
     * @return Cassandra\Map Replication options
     */
    function replicationOptions();

    /**
     * Returns whether the keyspace has durable writes enabled
     * @return string Whether durable writes are enabled
     */
    function hasDurableWrites();

    /**
     * Returns a table by name
     * @param  string               $name Table name
     * @return Cassandra\Table|null       Table instance or null
     */
    function table($name);

    /**
     * Returns all tables defined in this keyspace
     * @return array An array of `Cassandra\Table` instances
     */
    function tables();
}
