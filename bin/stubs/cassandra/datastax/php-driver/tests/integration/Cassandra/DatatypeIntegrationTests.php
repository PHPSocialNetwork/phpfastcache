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
 * A base class for data type integration tests
 */
abstract class DatatypeIntegrationTests extends BasicIntegrationTest {
    /**
     * Scalar Cassandra types to be used by data providers
     */
    public function scalarCassandraTypes() {
        return array(
            array(Type::ascii(), array("a", "b", "c")),
            array(Type::bigint(), array(new Bigint("1"), new Bigint("2"), new Bigint("3"))),
            array(Type::blob(), array(new Blob("x"), new Blob("y"), new Blob("z"))),
            array(Type::boolean(), array(true, false, true, false)),
            array(Type::date(), array(new Date(), new Date(0), new Date(-86400), new Date(86400))),
            array(Type::decimal(), array(new Decimal(1.1), new Decimal(2.2), new Decimal(3.3))),
            array(Type::double(), array(1.1, 2.2, 3.3, 4.4)),
            array(Type::float(), array(new Float(1.0), new Float(2.2), new Float(2.2))),
            array(Type::inet(), array(new Inet("127.0.0.1"), new Inet("127.0.0.2"), new Inet("127.0.0.3"))),
            array(Type::smallint(), array(Smallint::min(), Smallint::max(), new Smallint(0), new Smallint(74))),
            array(Type::text(), array("a", "b", "c", "x", "y", "z")),
            array(Type::time(), array(new Time(), new Time(0), new Time(1234567890))),
            array(Type::tinyint(), array(Tinyint::min(), Tinyint::max(), new Tinyint(0), new Tinyint(37))),
            array(Type::timestamp(), array(new Timestamp(123), new Timestamp(456), new Timestamp(789))),
            array(Type::timeuuid(), array(new Timeuuid(0), new Timeuuid(1), new Timeuuid(2))),
            array(Type::uuid(),  array(new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c2"),
                                       new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c3"),
                                       new Uuid("03398c99-c635-4fad-b30a-3b2c49f785c4"))),
            array(Type::varchar(), array("a", "b", "c", "x", "y", "z")),
            array(Type::varint(), array(new Varint(1), new Varint(2), new Varint(3))),
        );
    }

    /**
     * Create a table using $type for the value's type and insert $value using
     * positional parameters.
     *
     * @param $type Cassandra\Type
     * @param $value mixed
     */
    public function createTableInsertAndVerifyValueByIndex($type, $value) {
        $key = "key";
        $options = new ExecutionOptions(array('arguments' => array($key, $value)));
        $this->createTableInsertAndVerifyValue($type, $options, $key, $value);
    }

    /**
     * Create a table using $type for the value's type and insert $value using
     * named parameters.
     *
     * @param $type Cassandra\Type
     * @param $value mixed
     */
    public function createTableInsertAndVerifyValueByName($type, $value) {
        $key = "key";
        $options = new ExecutionOptions(array('arguments' => array("key" => $key, "value" => $value)));
        $this->createTableInsertAndVerifyValue($type, $options, $key, $value);
    }

    /**
     * Create a user type in the current keyspace
     *
     * @param $userType Cassandra\Type\UserType
     */
    public function createUserType($userType) {
        $query  = "CREATE TYPE IF NOT EXISTS %s (%s)";
        $fieldsString = implode(", ", array_map(function ($name, $type) {
            return "$name " . self::typeString($type);
        }, array_keys($userType->types()), $userType->types()));
        $query = sprintf($query, $this->userTypeString($userType), $fieldsString);
        $this->session->execute(new SimpleStatement($query));
    }

    /**
     * Create a table named for the CQL $type parameter
     *
     * @param $type Cassandra\Type
     * @return string Table name generated from $type
     */
    public function createTable($type) {
        $query = "CREATE TABLE IF NOT EXISTS %s (key text PRIMARY KEY, value %s)";

        $cqlType = $this->typeString($type);
        $tableName = "table_" . str_replace(array("-"), "", (string)(new Uuid()));

        $query = sprintf($query, $tableName, $cqlType);

        $this->session->execute(new SimpleStatement($query));

        return $tableName;
    }

    /**
     * Create a new table with specified type and insert and verify value
     *
     * @param $type Cassandra\Type
     * @param $options Cassandra\ExecutionOptions
     * @param $key string
     * @param $value mixed
     */
    protected function createTableInsertAndVerifyValue($type, $options, $key, $value) {
        $tableName = $this->createTable($type);

        $this->insertValue($tableName, $options);

        $this->verifyValue($tableName, $type, $key, $value);
    }

    /**
     * Insert a value into table
     *
     * @param $tableName string
     * @param $options Cassandra\ExecutionOptions
     */
    protected function insertValue($tableName, $options) {
        $insertQuery = "INSERT INTO $tableName (key, value) VALUES (?, ?)";

        $this->session->execute(new SimpleStatement($insertQuery), $options);
    }

    /**
     * Verify value
     *
     * @param $tableName string
     * @param $type Cassandra\Type
     * @param $key string
     * @param $value mixed
     */
    protected function verifyValue($tableName, $type, $key, $value) {
        $selectQuery = "SELECT * FROM $tableName WHERE key = ?";

        $options = new ExecutionOptions(array('arguments' => array($key)));

        $result = $this->session->execute(new SimpleStatement($selectQuery), $options);

        $this->assertEquals(count($result), 1);

        $row = $result->first();

        $this->assertEquals($row['value'], $value);
        $this->assertTrue($row['value'] == $value);
        if (isset($row['value'])) {
            $this->assertEquals(count($row['value']), count($value));
            if (is_object($row['value'])) {
                $this->assertEquals($row['value']->type(), $type);
            }
        }
    }

    /**
     * Generate a type string suitable for creating a new table or user type
     * using CQL
     *
     * @param $type Cassandra\Type
     * @return string String representation of type
     */
    public static function typeString($type) {
        if ($type instanceof Type\Tuple || $type instanceof Type\Collection ||
            $type instanceof Type\Map || $type instanceof Type\Set ||
            $type instanceof Type\UserType) {
            return sprintf("frozen<%s>", $type);
        } else {
            return (string)$type;
        }
    }

    /**
     * Generate a user type name string suitable for creating a new table or
     * user type using CQL
     *
     * @param $userType Cassandra\Type
     * @return string String representation of the UserType
     */
    public static function userTypeString($userType) {
        return sprintf("%s", implode("_", array_map(function ($name, $type) {
            return $name . str_replace(array("frozen", "<", " ", ",", ">"), "", $type);
        }, array_keys($userType->types()), $userType->types())));
    }
}

