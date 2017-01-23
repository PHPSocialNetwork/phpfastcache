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
class UserTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testDefinesUserTypeType()
    {
        $type = Type::userType('a', Type::varchar());
        $this->assertEquals('userType<a:varchar>', (string) $type);
        $types = $type->types();
        $this->assertEquals(Type::varchar(), $types['a']);
    }

    public function testCreatesUserTypeFromValues()
    {
        $udt = Type::userType('a', Type::varchar(), 'b', Type::int())
                    ->create('a', 'xyz', 'b', 123);
        $this->assertEquals(array('a' => 'xyz', 'b' => 123), $udt->values());
        $this->assertEquals('xyz', $udt->get('a'));
        $this->assertEquals(123, $udt->get('b'));
    }

    public function testCreatesEmptyUserType()
    {
        $udt = Type::userType('a', Type::varchar())->create();
        $this->assertEquals(1, count($udt));
        $this->assertEquals($udt->get('a'), null);

        $udt = Type::userType('a', Type::varchar(), 'b', Type::int())->create();
        $this->assertEquals(2, count($udt));
        $this->assertEquals($udt->get('a'), null);
        $this->assertEquals($udt->get('b'), null);

        $udt = Type::userType('a', Type::varchar(), 'b', Type::int(), 'c', Type::bigint())->create();
        $this->assertEquals(3, count($udt));
        $this->assertEquals($udt->get('a'), null);
        $this->assertEquals($udt->get('b'), null);
        $this->assertEquals($udt->get('c'), null);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Not enough name/type pairs,
     *                           udts can only be created from an even
     *                           number of name/type pairs, where each
     *                           odd argument is a name and each even
     *                           argument is a type,
     *                           e.g udt(name, type, name, type, name, type)'
     *                           contains 'argument must be a string, 1 given
     */
    public function testPreventsCreatingUserTypeTypeWithInvalidName()
    {
        Type::userType(Type::varchar());
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage Not enough name/value pairs,
     *                           udts can only be created from an even
     *                           number of name/value pairs, where each
     *                           odd argument is a name and each even
     *                           argument is a value,
     *                           e.g udt(name, value, name, value, name, value)'
     *                           contains 'argument must be a string, 1 given'.
     */
    public function testPreventsCreatingUserTypeWithInvalidName()
    {
        Type::userType('a', Type::varchar())->create(1);
    }


    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage argument must be a string, 1 given
     */
    public function testPreventsCreatingUserTypeWithUnsupportedTypes()
    {
        Type::userType('a', Type::varchar())->create('a', 1);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage type must be a valid Cassandra\Type, an
     *                           instance of Cassandra\Type\UnsupportedType given
     */
    public function testPreventsDefiningUserTypesWithUnsupportedTypes()
    {
        Type::userType('a', new UnsupportedType());
    }

    public function testWithNameOrWithKeyspace() {
        $userType = Type::userType('a', Type::int(), 'b', Type::varchar());
        $this->assertEquals($userType->name(), null);
        $this->assertEquals($userType->keyspace(), null);

        $userType1 = $userType->withName('abc');
        $types = $userType1->types();
        $this->assertEquals(count($types), 2);
        $this->assertEquals($types['a'], Type::int());
        $this->assertEquals($types['b'], Type::varchar());
        $this->assertEquals($userType1->name(), 'abc');
        $this->assertEquals($userType1->keyspace(), null);
        $this->assertEquals($userType->name(), null);
        $this->assertEquals($userType->keyspace(), null);

        $userType2 = $userType1->withKeyspace('xyz');
        $types = $userType2->types();
        $this->assertEquals(count($types), 2);
        $this->assertEquals($types['a'], Type::int());
        $this->assertEquals($types['b'], Type::varchar());
        $this->assertEquals($userType2->name(), 'abc');
        $this->assertEquals($userType2->keyspace(), 'xyz');
        $this->assertEquals($userType->name(), null);
        $this->assertEquals($userType->keyspace(), null);
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
            array(Type::userType('a', Type::int()),
                  Type::userType('a', Type::int())),
            array(Type::userType('a', Type::int(), 'b', Type::varchar()),
                  Type::userType('a', Type::int(), 'b', Type::varchar())),
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint()),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())),
            array(Type::userType('a', Type::collection(Type::int()), 'b', Type::set(Type::int())),
                  Type::userType('a', Type::collection(Type::int()), 'b', Type::set(Type::int())))
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
            // Different types
            array(Type::userType('a', Type::int()),
                  Type::userType('a', Type::varchar())),
            array(Type::userType('a', Type::int(), 'b', Type::varchar()),
                  Type::userType('a', Type::int(), 'b', Type::bigint())),
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::varint()),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())),
            array(Type::userType('a', Type::collection(Type::int()), 'b', Type::set(Type::varchar())),
                  Type::userType('a', Type::collection(Type::int()), 'b', Type::set(Type::int()))),
            // Different names
            array(Type::userType('a', Type::int()),
                  Type::userType('b', Type::int())),
            array(Type::userType('a', Type::int(), 'c', Type::varchar()),
                  Type::userType('b', Type::int(), 'c', Type::varchar())),
        );
    }
}
