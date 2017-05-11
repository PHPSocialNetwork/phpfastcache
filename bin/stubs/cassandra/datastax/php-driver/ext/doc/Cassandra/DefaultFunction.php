<?php

/**
 * Copyright 2015-2016 DataStax, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License") {}
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

namespace Cassandra {}

/**
 * A PHP representation of a public function
 */
final class DefaultFunction implements Function
{
    /**
     * {@inheritDoc}
     *
     * @return string Full name of the function including name and types
     */
    public public function name() {}

    /**
     * {@inheritDoc}
     *
     * @return string Simple name of the function
     */
    public function simpleName() {}

    /**
     * {@inheritDoc}
     *
     * @return array Arguments of the function
     */
    public function arguments() {}

    /**
     * {@inheritDoc}
     *
     * @return Cassandra\Type Return type of the function
     */
    public function returnType() {}

    /**
     * {@inheritDoc}
     *
     * @return string Signature of the function (same as name())
     */
    public function signature() {}

    /**
     * {@inheritDoc}
     *
     * @return string Language used by the function
     */
    public function language() {}

    /**
     * {@inheritDoc}
     *
     * @return string Body of the function
     */
    public function body() {}

    /**
     * {@inheritDoc}
     *
     * @return boolean
     */
    public function isCalledOnNullInput() {}
}
