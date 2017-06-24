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
 * The default retry policy. This policy retries a query, using the
 * request's original consistency level, in the following cases:
 *
 * * On a read timeout, if enough replicas replied but the data was not received.
 * * On a write timeout, if a timeout occurs while writing a distributed batch log.
 * * On unavailable, it will move to the next host.
 *
 * In all other cases the error will be returned.
 *
 */
final class DefaultPolicy implements RetryPolicy
{
}
