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
 * @requires extension cassandra
 */
class ExecutionOptionsTest extends \PHPUnit_Framework_TestCase
{
    public function testAllowsRetrievingSettingsByName()
    {
        $options = new ExecutionOptions(array(
            'consistency'        => \Cassandra::CONSISTENCY_ANY,
            'serial_consistency' => \Cassandra::CONSISTENCY_LOCAL_SERIAL,
            'page_size'          => 15000,
            'timeout'            => 15,
            'arguments'          => array('a', 1, 'b', 2, 'c', 3)
        ));

        $this->assertEquals(\Cassandra::CONSISTENCY_ANY, $options->consistency);
        $this->assertEquals(\Cassandra::CONSISTENCY_LOCAL_SERIAL, $options->serialConsistency);
        $this->assertEquals(15000, $options->pageSize);
        $this->assertEquals(15, $options->timeout);
        $this->assertEquals(array('a', 1, 'b', 2, 'c', 3), $options->arguments);
    }

    public function testReturnsNullValuesWhenRetrievingUndefinedSettingsByName()
    {
        $options = new ExecutionOptions(array());

        $this->assertNull($options->consistency);
        $this->assertNull($options->serialConsistency);
        $this->assertNull($options->pageSize);
        $this->assertNull($options->timeout);
        $this->assertNull($options->arguments);
    }
}
