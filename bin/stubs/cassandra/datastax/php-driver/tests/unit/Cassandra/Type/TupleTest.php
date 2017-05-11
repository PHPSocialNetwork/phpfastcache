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
class TupleTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinesTupleType()
    {
        $type = Type::tuple(Type::varchar(), Type::int());
        $this->assertEquals('tuple', $type->name());
        $this->assertEquals('tuple<varchar, int>', (string) $type);
        $types = $type->types();
        $this->assertEquals(Type::varchar(), $types[0]);
        $this->assertEquals(Type::int(), $types[1]);
    }

    public function testCreatesTupleFromValues()
    {
        $tuple = Type::tuple(Type::varchar(), Type::int())
                    ->create('xyz', 123);
        $this->assertEquals(array('xyz', 123), $tuple->values());
        $this->assertEquals('xyz', $tuple->get(0));
        $this->assertEquals(123, $tuple->get(1));
    }

    public function testCreatesEmptyTuple()
    {
        $tuple = Type::tuple(Type::varchar())->create();
        $this->assertEquals(1, count($tuple));
        $this->assertEquals($tuple->get(0), null);

        $tuple = Type::tuple(Type::varchar(), Type::int())->create();
        $this->assertEquals(2, count($tuple));
        $this->assertEquals($tuple->get(0), null);
        $this->assertEquals($tuple->get(1), null);

        $tuple = Type::tuple(Type::varchar(), Type::int(), Type::bigint())->create();
        $this->assertEquals(3, count($tuple));
        $this->assertEquals($tuple->get(0), null);
        $this->assertEquals($tuple->get(1), null);
        $this->assertEquals($tuple->get(2), null);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage argument must be a string, 1 given
     */
    public function testPreventsCreatingTupleWithInvalidType()
    {
        Type::tuple(Type::varchar())->create(1);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage type must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testPreventsDefiningTuplesWithUnsupportedTypes()
    {
        Type::tuple(new UnsupportedType());
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
            array(Type::tuple(Type::int()),
                  Type::tuple(Type::int())),
            array(Type::tuple(Type::int(), Type::varchar()),
                  Type::tuple(Type::int(), Type::varchar())),
            array(Type::tuple(Type::int(), Type::varchar(), Type::bigint()),
                  Type::tuple(Type::int(), Type::varchar(), Type::bigint())),
            array(Type::tuple(Type::collection(Type::int()), Type::set(Type::int())),
                  Type::tuple(Type::collection(Type::int()), Type::set(Type::int())))
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
            array(Type::tuple(Type::int()),
                  Type::tuple(Type::varchar())),
            array(Type::tuple(Type::int(), Type::varchar()),
                  Type::tuple(Type::int(), Type::bigint())),
            array(Type::tuple(Type::int(), Type::varchar(), Type::varint()),
                  Type::tuple(Type::int(), Type::varchar(), Type::bigint())),
            array(Type::tuple(Type::collection(Type::int()), Type::set(Type::varchar())),
                  Type::tuple(Type::collection(Type::int()), Type::set(Type::int())))
        );
    }
}
