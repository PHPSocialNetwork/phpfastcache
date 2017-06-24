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
 * Actual session implementation.
 *
 * @see Cassandra\Session
 */
final class DefaultSession implements Session
{
    /**
     * {@inheritDoc}
     *
     * @return Schema current schema.
     */
    public function schema() {}

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     *
     * @param Statement        $statement statement to be executed
     * @param ExecutionOptions $options   execution options (optional)
     *
     * @return Rows execution result
     */
    public function execute(Statement $statement, ExecutionOptions $options = null) {}

    /**
     * {@inheritDoc}
     *
     * @param Statement             $statement statement to be executed
     * @param ExecutionOptions|null $options   execution options (optional)
     *
     * @return Future future result
     */
    public function executeAsync(Statement $statement, ExecutionOptions $options = null) {}

    /**
     * {@inheritDoc}
     *
     * @throws Exception
     *
     * @param string                $cql     CQL statement string
     * @param ExecutionOptions|null $options execution options (optional)
     *
     * @return PreparedStatement prepared statement
     */
    public function prepare($cql, ExecutionOptions $options = null) {}

    /**
     * {@inheritDoc}
     *
     * @param string                $cql     CQL string to be prepared
     * @param ExecutionOptions|null $options preparation options
     *
     * @return Future statement
     */
    public function prepareAsync($cql, ExecutionOptions $options = null) {}

    /**
     * {@inheritDoc}
     *
     * @param float|null $timeout Timeout to wait for closure in seconds
     * @return void
     */
    public function close($timeout = null) {}

    /**
     * {@inheritDoc}
     *
     * @return Future future
     */
    public function closeAsync() {}
}
