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
 * Batch statements are used to execute a series of simple or prepared
 * statements.
 *
 * There are 3 types of batch statements:
 *  * `Cassandra::BATCH_LOGGED`   - this is the default batch type. This batch
 *    guarantees that either all or none of its statements will be executed.
 *    This behavior is achieved by writing a batch log on the coordinator,
 *    which slows down the execution somewhat.
 *  * `Cassandra::BATCH_UNLOGGED` - this batch will not be verified when
 *    executed, which makes it faster than a `LOGGED` batch, but means that
 *    some of its statements might fail, while others - succeed.
 *  * `Cassandra::BATCH_COUNTER`  - this batch is used for counter updates,
 *    which are, unlike other writes, not idempotent.
 *
 * @see Cassandra::BATCH_LOGGED
 * @see Cassandra::BATCH_UNLOGGED
 * @see Cassandra::BATCH_COUNTER
 */
final class BatchStatement implements Statement
{
    /**
     * Creates a new batch statement.
     *
     * @param int $type must be one of Cassandra::BATCH_* (default: Cassandra::BATCH_LOGGED).
     */
    public function __construct($type = \Cassandra::BATCH_LOGGED) {}

    /**
     * Adds a statement to this batch.
     *
     * @param Statement  $statement the statement to add
     * @param array|null $arguments positional or named arguments
     *
     * @throws Exception\InvalidArgumentException
     *
     * @return BatchStatement self
     */
    public function add(Statement $statement, array $arguments = null) {}
}
