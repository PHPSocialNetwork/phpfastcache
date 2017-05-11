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
class CollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinesCollectionType()
    {
        $type = Type::collection(Type::varchar());
        $this->assertEquals("list", $type->name());
        $this->assertEquals("list<varchar>", (string) $type);
        $this->assertEquals(Type::varchar(), $type->valueType());
    }

    public function testCreatesCollectionFromValues()
    {
        $list = Type::collection(Type::varchar())
                    ->create("a", "b", "c", "d", "e");
        $this->assertEquals(array("a", "b", "c", "d", "e"), $list->values());
        $this->assertEquals("a", $list->get(0));
        $this->assertEquals("b", $list->get(1));
        $this->assertEquals("c", $list->get(2));
        $this->assertEquals("d", $list->get(3));
        $this->assertEquals("e", $list->get(4));
    }

    public function testCreatesEmptyCollection()
    {
        $list = Type::collection(Type::varchar())->create();
        $this->assertEquals(0, count($list));
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage argument must be a string, 1 given
     */
    public function testPreventsCreatingCollectionWithUnsupportedTypes()
    {
        Type::collection(Type::varchar())->create(1);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage type must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testPreventsDefiningCollectionsWithUnsupportedTypes()
    {
        Type::collection(new UnsupportedType());
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
            array(Type::collection(Type::int()),
                  Type::collection(Type::int())),
            array(Type::collection(Type::collection(Type::int())),
                  Type::collection(Type::collection(Type::int()))),
            array(Type::collection(Type::set(Type::int())),
                  Type::collection(Type::set(Type::int()))),
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
            array(Type::collection(Type::varchar()),
                  Type::collection(Type::int())),
            array(Type::collection(Type::collection(Type::varchar())),
                  Type::collection(Type::collection(Type::int()))),
            array(Type::collection(Type::collection(Type::int())),
                  Type::collection(Type::set(Type::int()))),
        );
    }
}
