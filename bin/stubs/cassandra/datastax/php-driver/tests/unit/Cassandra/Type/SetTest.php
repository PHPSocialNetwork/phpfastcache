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

namespace Cassandra\Type;

use Cassandra\Type;

/**
 * @requires extension cassandra
 */
class SetTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinesSetType()
    {
        $type = Type::set(Type::varchar());
        $this->assertEquals("set", $type->name());
        $this->assertEquals("set<varchar>", (string) $type);
        $this->assertEquals(Type::varchar(), $type->valueType());
    }

    public function testCreatesSetFromValues()
    {
        $set = Type::set(Type::varchar())
                   ->create("a", "b", "c", "d", "e");
        $this->assertEquals(array("a", "b", "c", "d", "e"), $set->values());
    }

    public function testCreatesEmptySet()
    {
        $set = Type::set(Type::varchar())->create();
        $this->assertEquals(0, count($set));
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage argument must be a string, 1 given
     */
    public function testPreventsCreatingSetWithUnsupportedTypes()
    {
        Type::set(Type::varchar())->create(1);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage type must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testPreventsDefiningSetsWithUnsupportedTypes()
    {
        Type::set(new UnsupportedType());
    }

    /**
     * @dataProvider equalTypes
     */
    public function testCompareEquals($type1, $type2)
    {
        $this->assertEquals($type1, $type2);
        $this->assertTrue($type1 == $type2);
    }

    public function equalTypes()
    {
        return array(
            array(Type::set(Type::int()),
                  Type::set(Type::int())),
            array(Type::set(Type::collection(Type::int())),
                  Type::set(Type::collection(Type::int()))),
            array(Type::set(Type::set(Type::int())),
                  Type::set(Type::set(Type::int()))),
        );
    }

    /**
     * @dataProvider notEqualTypes
     */
    public function testCompareNotEquals($type1, $type2)
    {
        $this->assertNotEquals($type1, $type2);
        $this->assertFalse($type1 == $type2);
    }

    public function notEqualTypes()
    {
        return array(
            array(Type::set(Type::varchar()),
                  Type::set(Type::int())),
            array(Type::set(Type::collection(Type::varchar())),
                  Type::set(Type::collection(Type::int()))),
            array(Type::set(Type::collection(Type::int())),
                  Type::set(Type::set(Type::int()))),
        );
    }
}
