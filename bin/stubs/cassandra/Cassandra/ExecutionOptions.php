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
 * Request execution options.
 *
 * @see Session::execute()
 * @see Session::executeAsync()
 * @see Session::prepare()
 * @see Session::prepareAsync()
 */
final class ExecutionOptions
{
    /**
     * Creates a new options object for execution.
     *
     * * array['arguments']          array                  An array or positional or named arguments
     * * array['consistency']        int                    One of Cassandra::CONSISTENCY_*
     * * array['timeout']            int|null               A number of seconds or null
     * * array['page_size']          int                    A number of rows to include in result for paging
     * * array['paging_state_token'] string                 A string token use to resume from the state of a previous result set
     * * array['retry_policy']       Cassandra\RetryPolicy  A retry policy that is used to handle server-side failures for this request
     * * array['serial_consistency'] int                    Either Cassandra::CONSISTENCY_SERIAL or Cassandra::CONSISTENCY_LOCAL_SERIAL
     * * array['timestamp']          int|string             Either an integer or integer string timestamp that represents the number
     *                                                      of microseconds since the epoch.
     *
     * @throws Exception\InvalidArgumentException
     *
     * @param array $options various execution options
     */
    public function __construct(array $options = null) {}
}
