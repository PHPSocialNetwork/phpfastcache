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
final class DefaultKeyspace implements Keyspace
{
    /**
     * {@inheritDoc}
     *
     * @return string Name
     */
    public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return string Replication class
     */
    public function replicationClassName() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Map Replication options
     */
    public function replicationOptions() {}

    /**
     * {@inheritDoc}
     *
     * @return string Whether durable writes are enabled
     */
    public function hasDurableWrites() {}

    /**
     * {@inheritDoc}
     *
     * @param  string          $name  Table name
     * @return Cassandra\Table        Table instance or null
     */
    public function table($name) {}

    /**
     * {@inheritDoc}
     *
     * @return array An array of `Cassandra\Table` instances
     */
    public function tables() {}
}
