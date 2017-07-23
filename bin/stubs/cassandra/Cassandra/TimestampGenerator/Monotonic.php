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

namespace Cassandra\TimestampGenerator;

use Cassandra\TimestampGenerator;

/**
 * A timestamp generator that generates monotonically increasing timestamps
 * client-side. The timestamps generated have a microsecond granularity with
 * the sub-millisecond part generated using a counter. The implementation
 * guarantees that no more than 1000 timestamps will be generated for a given
 * clock tick even if shared by multiple session objects. If that rate is
 * exceeded then a warning is logged and timestamps stop incrementing until
 * the next clock tick.
 */
final class Monotonic implements TimestampGenerator
{
}
