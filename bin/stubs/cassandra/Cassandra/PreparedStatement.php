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
 * Prepared statements are faster to execute because the server doesn't need
 * to process a statement's CQL during the execution.
 *
 * With token-awareness enabled in the driver, prepared statements are even
 * faster, because they are sent directly to replica nodes and avoid the extra
 * network hop.
 *
 * @see Session::prepare()
 */
final class PreparedStatement implements Statement
{
}
