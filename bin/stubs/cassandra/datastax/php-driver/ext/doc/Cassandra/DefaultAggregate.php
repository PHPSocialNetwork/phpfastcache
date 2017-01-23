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
 * A PHP representation of an aggregate
 */
final class DefaultAggregate implements Aggregate
{
    /**
     * {@inheritDoc}
     *
     * @return string Full name of the aggregate including name and types
     */
    public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return string Simple name of the aggregate
     */
    public function simpleName() {}

    /**
     * {@inheritDoc}
     *
     * @return array Argument types of the aggregate
     */
    public function argumentTypes() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Function Final public function of the aggregate
     */
    public function finalFunction() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Function State public function of the aggregate
     */
    public function stateFunction() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Value Initial condition of the aggregate
     */
    public function initialCondition() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Type Return type of the aggregate
     */
    public function returnType() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Type State type of the aggregate
     */
    public function stateType() {}

    /**
     * {@inheritDoc}
     *
     * @return string Signature of the aggregate (same as name())
     */
    public function signature() {}
}
