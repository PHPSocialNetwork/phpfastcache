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

namespace Cassandra\Exception;

/**
 * UnavailableException is raised when a coordinator detected that there aren't
 * enough replica nodes available to fulfill the request.
 *
 * NOTE: Request has not even been forwarded to the replica nodes in this case.
 * @see https://github.com/apache/cassandra/blob/cassandra-2.1/doc/native_protocol_v1.spec#L667-L677 Description of the Unavailable error in the native protocol v1 spec.
 */
class UnavailableException extends ExecutionException {}
