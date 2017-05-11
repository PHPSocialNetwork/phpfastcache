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
class UserTypeValueTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Unsupported type 'invalid type'
     */
    public function testSupportsOnlyCassandraTypes()
    {
        new UserTypeValue(array('name1' => 'invalid type'));
    }

    /**
     * @dataProvider cassandraTypes
     */
    public function testSupportsAllCassandraTypes($type)
    {
        new UserTypeValue(array('name1' => $type));
    }

    /**
     * @dataProvider cassandraTypes
     */
    public function testReturnsItsType($type)
    {
        $udt = new UserTypeValue(array('name1' => $type));
        $types = $udt->type()->types();
        $this->assertEquals($type, $types['name1']);
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
    public function testScalarValues($type, $value, $valueCopy)
    {
        $udt = new UserTypeValue(array('name1' => $type));
        $udt->set('name1', $value);
        $this->assertEquals(1, count($udt));
        $this->assertEquals($udt->get('name1'), $valueCopy);
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

    public function testSetAndGetFields()
    {
        $fields = array('a' => Type::int(), 'b' => Type::text(), 'c' => Type::bigint());
        $udt = new UserTypeValue($fields);
        $udt->set('a', 1);
        $udt->set('b', 'xyz');
        $udt->set('c', new Bigint('123'));

        $this->assertEquals($udt->get('a'), 1);
        $this->assertEquals($udt->get('b'), 'xyz');
        $this->assertEquals($udt->get('c'), new Bigint('123'));

        $other = new UserTypeValue($fields);
        $other->set('a', 1);
        $other->set('b', 'xyz');
        $other->set('c', new Bigint('123'));

        $this->assertEquals($udt, $other);
    }

    public function testEquals()
    {
        $fields = array('a' => Type::int(), 'b' => Type::text(), 'c' => Type::bigint());

        $udt = new UserTypeValue($fields);
        $udt->set('a', 1);
        $udt->set('b', 'xyz');
        $udt->set('c', new Bigint('123'));

        $this->assertEquals(count($udt), 3);

        $other = new UserTypeValue($fields);
        $other->set('a', 1);
        $other->set('b', 'xyz');
        $other->set('c', new Bigint('123'));

        $this->assertEquals($udt, $other);
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid name 'invalid'
     */
    public function testSetInvalidName()
    {
        $udt = new UserTypeValue(array('name1' => Type::int()));
        $udt->set('invalid', 42);
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  Invalid name 'invalid'
     */
    public function testGetInvalidName()
    {
        $udt = new UserTypeValue(array('name1' => Type::int()));
        $udt->set('name1', 42);
        $udt->get('invalid');
    }

    /**
     * @expectedException         InvalidArgumentException
     * @expectedExceptionMessage  argument must be an int, 'text' given
     */
    public function testInvalidType()
    {
        $udt = new UserTypeValue(array('name1' => Type::int()));
        $udt->set('name1', 'text');
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
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create(),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create()),
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create('a', 1, 'b', 'x', 'c', new Bigint(99)),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create('a', 1, 'b', 'x', 'c', new Bigint(99))),
            array(Type::userType('a', $setType, 'b', Type::varchar())->create('a', $setType->create(1, 2, 3), 'b', 'x'),
                  Type::userType('a', $setType, 'b', Type::varchar())->create('a', $setType->create(1, 2, 3), 'b', 'x'))
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
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::varint())->create(),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create()),
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create(),
                  Type::userType('x', Type::int(), 'y', Type::varchar(), 'z', Type::bigint())->create()),
            array(Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create('a', 1, 'b', 'x', 'c', new Bigint(99)),
                  Type::userType('a', Type::int(), 'b', Type::varchar(), 'c', Type::bigint())->create('a', 2, 'b', 'y', 'c', new Bigint(999))),
            array(Type::userType('a', $setType, 'b', Type::varchar())->create('a', $setType->create(1, 2, 3), 'b', 'x'),
                  Type::userType('a', $setType, 'b', Type::varchar())->create('a', $setType->create(4, 5, 6), 'b', 'x'))
        );
    }
}
