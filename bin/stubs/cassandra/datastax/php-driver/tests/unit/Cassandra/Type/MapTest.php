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
class MapTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinesMapType()
    {
        $type = Type::map(Type::varchar(), Type::int());
        $this->assertEquals("map", $type->name());
        $this->assertEquals("map<varchar, int>", (string) $type);
        $this->assertEquals(Type::varchar(), $type->keyType());
        $this->assertEquals(Type::int(), $type->valueType());
    }

    public function testCreatesMapFromValues()
    {
        $map = Type::map(Type::varchar(), Type::int())
                   ->create("a", 1, "b", 2, "c", 3, "d", 4, "e", 5);
        $this->assertEquals(array("a", "b", "c", "d", "e"), $map->keys());
        $this->assertEquals(array(1, 2, 3, 4, 5), $map->values());
        $this->assertEquals(1, $map["a"]);
        $this->assertEquals(2, $map["b"]);
        $this->assertEquals(3, $map["c"]);
        $this->assertEquals(4, $map["d"]);
        $this->assertEquals(5, $map["e"]);
    }

    public function testCreatesEmptyMap()
    {
        $map = Type::map(Type::varchar(), Type::int())->create();
        $this->assertEquals(0, count($map));
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Not enough values, maps can only be created
     *                           from an even number of values, where each odd
     *                           value is a key and each even value is a value,
     *                           e.g create(key, value, key, value, key, value)
     */
    public function testPreventsCreatingMapWithoutEnoughValues()
    {
        Type::map(Type::varchar(), Type::int())
            ->create("a", 1, "b", 2, "c", 3, "d", 4, "e");
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage argument must be a string, 1 given
     */
    public function testPreventsCreatingMapWithUnsupportedTypes()
    {
        Type::map(Type::varchar(), Type::int())
            ->create(1, "a");
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage keyType must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testPreventsDefiningMapsWithUnsupportedTypes()
    {
        Type::map(new UnsupportedType(), Type::varchar());
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
            array(Type::map(Type::int(), Type::varchar()),
                  Type::map(Type::int(), Type::varchar())),
            array(Type::map(Type::varchar(), Type::collection(Type::int())),
                  Type::map(Type::varchar(), Type::collection(Type::int()))),
            array(Type::map(Type::collection(Type::int()), Type::varchar()),
                  Type::map(Type::collection(Type::int()), Type::varchar())),
            array(Type::map(Type::map(Type::int(), Type::varchar()), Type::varchar()),
                  Type::map(Type::map(Type::int(), Type::varchar()), Type::varchar())),
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
            array(Type::map(Type::int(), Type::varchar()),
                  Type::map(Type::varchar(), Type::int())),
            array(Type::map(Type::collection(Type::varchar()), Type::int()),
                  Type::map(Type::collection(Type::int()), Type::int())),
            array(Type::map(Type::map(Type::int(), Type::varchar()), Type::varchar()),
                  Type::map(Type::map(Type::varchar(), Type::int()), Type::varchar())),
        );
    }
}
