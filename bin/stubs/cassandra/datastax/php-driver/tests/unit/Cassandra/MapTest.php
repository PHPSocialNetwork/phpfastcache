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

use Cassandra\Type;

/**
 * @requires extension cassandra
 */
class MapTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  keyType must be a string or an instance of Cassandra\Type, an instance of stdClass given
     */
    public function testInvalidKeyType()
    {
        new Map(new \stdClass(), \Cassandra::TYPE_VARCHAR);
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Unsupported type 'custom type'
     */
    public function testUnsupportedStringKeyType()
    {
        new Map('custom type', \Cassandra::TYPE_VARCHAR);
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage keyType must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testUnsupportedKeyType()
    {
        new Map(new Type\UnsupportedType(), Type::varchar());
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  valueType must be a string or an instance of Cassandra\Type, an instance of stdClass given
     */
    public function testInvalidValueType()
    {
        new Map(\Cassandra::TYPE_VARCHAR, new \stdClass());
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Unsupported type 'custom type'
     */
    public function testUnsupportedStringValueType()
    {
        new Map(\Cassandra::TYPE_VARCHAR, 'custom type');
    }

    /**
     * @expectedException        InvalidArgumentException
     * @expectedExceptionMessage valueType must be a valid Cassandra\Type,
     *                           an instance of Cassandra\Type\UnsupportedType given
     */
    public function testUnsupportedValueType()
    {
        new Map(Type::varchar(), new Type\UnsupportedType());
    }

    public function testSupportsKeyBasedAccess()
    {
        $map = Type::map(Type::varint(), Type::varchar())->create();
        $this->assertEquals(0, count($map));
        $map->set(new Varint('123'), 'value');
        $this->assertEquals(1, count($map));
        $this->assertTrue($map->has(new Varint('123')));
        $this->assertEquals('value', $map->get(new Varint('123')));
        $map->set(new Varint('123'), 'another value');
        $this->assertEquals(1, count($map));
        $this->assertEquals('another value', $map->get(new Varint('123')));
    }

    /**
     * @dataProvider scalarTypes
     */
    public function testScalarKeys($keyType, $keyValue, $keyValueCopy)
    {
        $map = Type::map($keyType, Type::varchar())->create();
        $map->set($keyValue, "value");
        $this->assertEquals(1, count($map));
        $this->assertEquals($map->get($keyValue), "value");
        $this->assertEquals($map->get($keyValueCopy), "value");
        $this->assertTrue($map->has($keyValue));
        $this->assertTrue($map->has($keyValueCopy));
        $map->remove($keyValue);
        $this->assertEquals(0, count($map));
    }

    public function scalarTypes()
    {
        return array(
            array(Type::ascii(), "ascii", "ascii"),
            array(Type::bigint(), new Bigint("9223372036854775807"), new Bigint("9223372036854775807")),
            array(Type::blob(), new Blob("blob"), new Blob("blob")),
            array(Type::boolean(), true, true),
            array(Type::counter(), new Bigint(123), new Bigint(123)),
            array(Type::decimal(), new Decimal("3.14159265359"), new Decimal("3.14159265359")),
            array(Type::double(), 3.14159, 3.14159),
            array(Type::float(), new Float(3.14159), new Float(3.14159)),
            array(Type::inet(), new Inet("127.0.0.1"), new Inet("127.0.0.1")),
            array(Type::int(), 123, 123),
            array(Type::text(), "text", "text"),
            array(Type::timestamp(), new Timestamp(123), new Timestamp(123)),
            array(Type::timeuuid(), new Timeuuid(0), new Timeuuid(0)),
            array(Type::uuid(), new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c2"), new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c2")),
            array(Type::varchar(), "varchar", "varchar"),
            array(Type::varint(), new Varint("9223372036854775808"), new Varint("9223372036854775808"))
        );
    }

    /**
     * @dataProvider compositeTypes
     */
    public function testCompositeKeys($keyType)
    {
        $map = Type::map($keyType, Type::varchar())->create();

        $map->set($keyType->create("a", "1", "b", "2"), "value1");
        $this->assertEquals($map->get($keyType->create("a", "1", "b", "2")), "value1");
        $this->assertEquals(1, count($map));

        $map->set($keyType->create("c", "3", "d", "4", "e", "5"), "value2");
        $this->assertEquals($map->get($keyType->create("c", "3", "d", "4", "e", "5")), "value2");
        $this->assertEquals(2, count($map));

        $map->remove($keyType->create("a", "1", "b", "2"));
        $this->assertFalse($map->has($keyType->create("a", "1", "b", "2")));
        $this->assertEquals(1, count($map));

        $map->remove($keyType->create("c", "3", "d", "4", "e", "5"));
        $this->assertFalse($map->has($keyType->create("c", "3", "d", "4", "e", "5")));
        $this->assertEquals(0, count($map));
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
     * @expectedExceptionMessage  Unsupported type 'custom type'
     */
    public function testSupportsOnlyCassandraTypesForKeys()
    {
        new Map('custom type', \Cassandra::TYPE_VARINT);
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Unsupported type 'another custom type'
     */
    public function testSupportsOnlyCassandraTypesForValues()
    {
        new Map(\Cassandra::TYPE_VARINT, 'another custom type');
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid value: null is not supported inside maps
     */
    public function testSupportsNullValues()
    {
        $map = new Map(\Cassandra::TYPE_VARCHAR, \Cassandra::TYPE_VARCHAR);
        $map->set("test", null);
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid key: null is not supported inside maps
     */
    public function testSupportsNullKeys()
    {
        $map = new Map(\Cassandra::TYPE_VARCHAR, \Cassandra::TYPE_VARCHAR);
        $map->set(null, "test");
    }

    public function testSupportsForeachIteration()
    {
        $keys = array(new Varint('1'), new Varint('2'), new Varint('3'),
                      new Varint('4'), new Varint('5'), new Varint('6'),
                      new Varint('7'), new Varint('8'));
        $values = array('a', 'b', 'c',
                        'd', 'e', 'f',
                        'g', 'h');
        $map = new Map(\Cassandra::TYPE_VARINT, \Cassandra::TYPE_VARCHAR);

        for ($i = 0; $i < count($keys); $i++) {
            $map->set($keys[$i], $values[$i]);
        }

        $index = 0;
        foreach ($map as $value) {
            $this->assertEquals($values[$index], $value);
            $index++;
        }

        $index = 0;
        foreach ($map as $key => $value) {
            $this->assertEquals($keys[$index], $key);
            $this->assertEquals($values[$index], $value);
            $index++;
        }
    }

    public function testSupportsRetrievingKeysAndValues()
    {
        $keys = array(new Varint('1'), new Varint('2'), new Varint('3'),
                      new Varint('4'), new Varint('5'), new Varint('6'),
                      new Varint('7'), new Varint('8'));
        $values = array('a', 'b', 'c',
                        'd', 'e', 'f',
                        'g', 'h');
        $map = new Map(\Cassandra::TYPE_VARINT, \Cassandra::TYPE_VARCHAR);

        for ($i = 0; $i < count($keys); $i++) {
            $map->set($keys[$i], $values[$i]);
        }

        $this->assertEquals($keys, $map->keys());
        $this->assertEquals($values, $map->values());

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
            array(Type::map(Type::int(), Type::varchar())->create(),
                  Type::map(Type::int(), Type::varchar())->create()),
            array(Type::map(Type::int(), Type::varchar())->create(1, 'a', 2, 'b', 3, 'c'),
                  Type::map(Type::int(), Type::varchar())->create(1, 'a', 2, 'b', 3, 'c')),
            array(Type::map($setType, Type::varchar())->create($setType->create(1, 2, 3), 'a', $setType->create(4, 5, 6), 'b'),
                  Type::map($setType, Type::varchar())->create($setType->create(1, 2, 3), 'a', $setType->create(4, 5, 6), 'b'))
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
            array(Type::map(Type::int(), Type::int())->create(),
                  Type::map(Type::int(), Type::varchar())->create()),
            array(Type::map(Type::int(), Type::varchar())->create(1, 'a', 2, 'b', 3, 'c'),
                  Type::map(Type::int(), Type::varchar())->create(1, 'a')),
            array(Type::map($setType, Type::varchar())->create($setType->create(4, 5, 6), 'a', $setType->create(7, 8, 9), 'b'),
                  Type::map($setType, Type::varchar())->create($setType->create(1, 2, 3), 'a', $setType->create(4, 5, 6), 'b'))
        );
    }
}
