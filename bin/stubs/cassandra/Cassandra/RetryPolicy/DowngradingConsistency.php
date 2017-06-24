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

namespace Cassandra\RetryPolicy;

use Cassandra\RetryPolicy;

/**
 * A retry policy that will downgrade the consistency of a request in
 * an attempt to save a request in cases where there is any chance of success. A
 * write request will succeed if there is at least a single copy persisted and a
 * read request will succeed if there is some data available even if it increases
 * the risk of reading stale data. This policy will retry in the same scenarios as
 * the default policy, and it will also retry in the following case:
 *
 * * On a read timeout, if some replicas responded but is lower than
 *   required by the current consistency level then retry with a lower
 *   consistency level
 * * On a write timeout, Retry unlogged batches at a lower consistency level
 *   if at least one replica responded. For single queries and batch if any
 *   replicas responded then consider the request successful and swallow the
 *   error.
 * * On unavailable, retry at a lower consistency if at lease one replica
 *   responded.
 *
 * Important: This policy may attempt to retry requests with a lower
 * consistency level. Using this policy can break consistency guarantees.
 */
final class DowngradingConsistency implements RetryPolicy
{
}

