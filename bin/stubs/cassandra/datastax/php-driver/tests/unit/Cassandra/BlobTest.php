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
class BlobTest extends \PHPUnit_Framework_TestCase
{
    public function testHexEncodesString()
    {
        $blob = new Blob("Hi");
        $this->assertEquals("0x4869", $blob->__toString());
        $this->assertEquals("0x4869", $blob->bytes());
    }

    public function testReturnsOriginalBytesAsBinaryString()
    {
        $blob = new Blob("Hi");
        $this->assertEquals("Hi", (string) $blob->toBinaryString());
    }

    /**
     * @dataProvider equalTypes
     */
    public function testCompareEquals($value1, $value2)
    {
        $this->assertEquals($value1, $value2);
        $this->assertTrue($value1 == $value2);
    }

    public function equalTypes()
    {
        return array(
            array(new Blob("0x1234"), new Blob("0x1234")),
        );
    }

    /**
     * @dataProvider notEqualTypes
     */
    public function testCompareNotEquals($value1, $value2)
    {
        $this->assertNotEquals($value1, $value2);
        $this->assertFalse($value1 == $value2);
    }

    public function notEqualTypes()
    {
        return array(
            array(new Blob("0x1234"), new Blob("0x4567")),
        );
    }
}
