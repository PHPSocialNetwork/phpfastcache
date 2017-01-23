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
class CollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  type must be a string or an instance of Cassandra\Type, an instance of stdClass given
     */
    public function testInvalidType()
    {
        new Collection(new \stdClass());
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Unsupported type 'custom type'
     */
    public function testUnsupportedStringType()
    {
        new Collection('custom type');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage type must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testUnsupportedType()
    {
        new Collection(new Type\UnsupportedType());
    }

    /**
     * @dataProvider cassandraTypes
     */
    public function testSupportsAllCassandraStringTypes($type)
    {
        $list = new Collection($type);
        $this->assertEquals($type, $list->type()->valueType());
    }

    public function cassandraTypes()
    {
        return array(
            array(\Cassandra::TYPE_ASCII),
            array(\Cassandra::TYPE_BIGINT),
            array(\Cassandra::TYPE_BLOB),
            array(\Cassandra::TYPE_BOOLEAN),
            array(\Cassandra::TYPE_COUNTER),
            array(\Cassandra::TYPE_DECIMAL),
            array(\Cassandra::TYPE_DOUBLE),
            array(\Cassandra::TYPE_FLOAT),
            array(\Cassandra::TYPE_INT),
            array(\Cassandra::TYPE_TEXT),
            array(\Cassandra::TYPE_TIMESTAMP),
            array(\Cassandra::TYPE_UUID),
            array(\Cassandra::TYPE_VARCHAR),
            array(\Cassandra::TYPE_VARINT),
            array(\Cassandra::TYPE_TIMEUUID),
            array(\Cassandra::TYPE_INET),
        );
    }

    /**
     * @dataProvider scalarTypes
     */
    public function testScalarKeys($type, $value)
    {
        $list = Type::collection($type)->create();
        $list->add($value);
        $this->assertEquals(1, count($list));
        $this->assertEquals($list->get(0), $value);
        $this->assertEquals($list->find($value), 0);
    }

    public function scalarTypes()
    {
        return array(
            array(Type::ascii(), "ascii"),
            array(Type::bigint(), new Bigint("9223372036854775807")),
            array(Type::blob(), new Blob("blob")),
            array(Type::boolean(), true),
            array(Type::counter(), new Bigint(123)),
            array(Type::decimal(), new Decimal("3.14159265359")),
            array(Type::double(), 3.14159),
            array(Type::float(), new Float(3.14159)),
            array(Type::inet(), new Inet("127.0.0.1")),
            array(Type::int(), 123),
            array(Type::text(), "text"),
            array(Type::timestamp(), new Timestamp(123)),
            array(Type::timeuuid(), new Timeuuid(0)),
            array(Type::uuid(), new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c2")),
            array(Type::varchar(), "varchar"),
            array(Type::varint(), new Varint("9223372036854775808"))
        );
    }

    /**
     * @dataProvider compositeTypes
     */
    public function testCompositeKeys($type)
    {
        $list = Type::collection($type)->create();

        $list->add($type->create("a", "1", "b", "2"));
        $this->assertEquals($list->find($type->create("a", "1", "b", "2")), 0);
        $this->assertEquals(1, count($list));

        $list->add($type->create("c", "3", "d", "4", "e", "5"));
        $this->assertEquals($list->find($type->create("c", "3", "d", "4", "e", "5")), 1);
        $this->assertEquals(2, count($list));

        $list->remove(0);
        $this->assertNull($list->find($type->create("a", "1", "b", "2")));
        $this->assertEquals(1, count($list));

        $list->remove(1);
        $this->assertNull($list->find($type->create("c", "3", "d", "4", "e", "5")));
        $this->assertEquals(0, count($list));
    }

    public function compositeTypes()
    {
        return array(
            array(Type::map(Type::varchar(), Type::varchar())),
            array(Type::set(Type::varchar())),
            array(Type::collection(Type::varchar()))
        );
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  argument must be an instance of Cassandra\Varint, an instance of Cassandra\Decimal given
     */
    public function testValidatesTypesOfElements()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $list->add(new Decimal('123'));
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid value: null is not supported inside collections
     */
    public function testSupportsNullValues()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $list->add(null);
    }

    public function testAddsAllElements()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $list->add(new Varint('1'), new Varint('2'), new Varint('3'),
                   new Varint('4'), new Varint('5'), new Varint('6'),
                   new Varint('7'), new Varint('8'));

        $this->assertEquals(8, $list->count());
        $this->assertEquals(
            array(
                new Varint('1'), new Varint('2'), new Varint('3'),
                new Varint('4'), new Varint('5'), new Varint('6'),
                new Varint('7'), new Varint('8')
            ),
            $list->values()
        );
    }

    public function testReturnsNullWhenCannotFindIndex()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $this->assertSame(null, $list->find(new Varint('1')));
    }

    public function testFindsIndexOfAnElement()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $list->add(new Varint('1'), new Varint('2'), new Varint('3'),
                   new Varint('4'), new Varint('5'), new Varint('6'),
                   new Varint('7'), new Varint('8'));

        $this->assertEquals(0, $list->find(new Varint('1')));
        $this->assertEquals(1, $list->find(new Varint('2')));
        $this->assertEquals(2, $list->find(new Varint('3')));
        $this->assertEquals(3, $list->find(new Varint('4')));
        $this->assertEquals(4, $list->find(new Varint('5')));
        $this->assertEquals(5, $list->find(new Varint('6')));
        $this->assertEquals(6, $list->find(new Varint('7')));
        $this->assertEquals(7, $list->find(new Varint('8')));
    }

    public function testGetsElementByIndex()
    {
        $list = new Collection(\Cassandra::TYPE_VARINT);
        $list->add(new Varint('1'), new Varint('2'), new Varint('3'),
                   new Varint('4'), new Varint('5'), new Varint('6'),
                   new Varint('7'), new Varint('8'));

        $this->assertEquals(new Varint('1'), $list->get(0));
        $this->assertEquals(new Varint('2'), $list->get(1));
        $this->assertEquals(new Varint('3'), $list->get(2));
        $this->assertEquals(new Varint('4'), $list->get(3));
        $this->assertEquals(new Varint('5'), $list->get(4));
        $this->assertEquals(new Varint('6'), $list->get(5));
        $this->assertEquals(new Varint('7'), $list->get(6));
        $this->assertEquals(new Varint('8'), $list->get(7));
    }

    public function testSupportsForeachIteration()
    {
        $values = array(new Varint('1'), new Varint('2'), new Varint('3'),
                        new Varint('4'), new Varint('5'), new Varint('6'),
                        new Varint('7'), new Varint('8'));
        $list = new Collection(\Cassandra::TYPE_VARINT);

        foreach ($values as $value) {
            $list->add($value);
        }

        $index = 0;
        foreach ($list as $value) {
            $this->assertEquals($values[$index], $value);
            $index++;
        }

        $index = 0;
        foreach ($list as $key => $value) {
            $this->assertEquals($key, $index);
            $this->assertEquals($values[$key], $value);
            $index++;
        }
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
        $setType = Type::set(Type::int());
        return array(
            array(Type::collection(Type::int())->create(),
                  Type::collection(Type::int())->create()),
            array(Type::collection(Type::int())->create(1, 2, 3),
                  Type::collection(Type::int())->create(1, 2, 3)),
            array(Type::collection(Type::varchar())->create('a', 'b', 'c'),
                  Type::collection(Type::varchar())->create('a', 'b', 'c')),
            array(Type::collection($setType)->create($setType->create(1, 2, 3)),
                  Type::collection($setType)->create($setType->create(1, 2, 3))),
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
        $setType = Type::set(Type::int());
        return array(
            array(Type::collection(Type::int())->create(),
                  Type::collection(Type::varchar())->create()),
            array(Type::collection(Type::int())->create(1, 2, 3),
                  Type::collection(Type::int())->create(4, 5, 6)),
            array(Type::collection(Type::int())->create(1, 2, 3),
                  Type::collection(Type::int())->create(1)),
            array(Type::collection(Type::varchar())->create('a', 'b', 'c'),
                  Type::collection(Type::varchar())->create('a', 'b', 'd')),
            array(Type::collection($setType)->create($setType->create(1, 2, 3)),
                  Type::collection($setType)->create($setType->create(4, 5, 6))),
        );
    }
}
