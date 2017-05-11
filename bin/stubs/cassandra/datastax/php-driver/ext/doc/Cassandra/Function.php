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
 * A PHP representation of a function
 */
interface Function
{
    /**
     * Returns the full name of the function
     * @return string Full name of the function including name and types
     */
    function name();

    /**
     * Returns the simple name of the function
     * @return string Simple name of the function
     */
    function simpleName();

    /**
     * Returns the arguments of the function
     * @return array Arguments of the function
     */
    function arguments();

    /**
     * Returns the return type of the function
     * @return Cassandra\Type Return type of the function
     */
    function returnType();

    /**
     * Returns the signature of the function
     * @return string Signature of the function (same as name())
     */
    function signature();

    /**
     * Returns the lanuage of the function
     * @return string Language used by the function
     */
    function language();

    /**
     * Returns the body of the function
     * @return string Body of the function
     */
    function body();

    /**
     * Returns whether the function is called when the input columns are null
     * @return boolean
     */
    function isCalledOnNullInput();
}
