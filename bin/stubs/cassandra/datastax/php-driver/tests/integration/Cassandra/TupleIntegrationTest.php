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
 * Tuple integration tests.
 *
 * @cassandra-version-2.1
 */
class TupleIntegrationTest extends CollectionsIntegrationTest {
    /**
     * Tuples using scalar/simple datatypes
     *
     * This test will ensure that the PHP driver supports the tuples collection
     * with all PHP driver supported scalar/simple datatypes.
     *
     * @test
     * @ticket PHP-58
     * @dataProvider tupleWithScalarTypes
     */
    public function testScalarTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for tuples with scalar types
     */
    public function tupleWithScalarTypes() {
        return array_map(function ($cassandraType) {
            $tupleType = Type::tuple($cassandraType[0]);
            $tuple = $tupleType->create();
            $tuple->set(0, $cassandraType[1][0]);
            return array($tupleType, $tuple);
        }, $this->scalarCassandraTypes());
    }

    /**
     * Tuples with composite types
     *
     * This test ensures that tuples work with other collections
     * and other composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider tupleWithCompositeTypes
     */
    public function testCompositeTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for tuples with composite types
     */
    public function tupleWithCompositeTypes() {
        return array_map(function ($cassandraType) {
            $tupleType = Type::tuple($cassandraType[0]);
            $tuple = $tupleType->create();
            $tuple->set(0, $cassandraType[1][0]);
            return array($tupleType, $tuple);
        }, $this->compositeCassandraTypes());
    }

    /**
     * Tuples with nested composite types
     *
     * This test ensures that tuples work with other nested collections
     * and other nested composite types such as UDTs and tuples.
     *
     * @test
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider tupleWithNestedTypes
     */
    public function testNestedTypes($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for tuples with nested composite types
     */
    public function tupleWithNestedTypes() {
        return array_map(function ($cassandraType) {
            $tupleType = Type::tuple($cassandraType[0]);
            $tuple = $tupleType->create();
            $tuple->set(0, $cassandraType[1][0]);
            return array($tupleType, $tuple);
        }, $this->nestedCassandraTypes());
    }

    /**
     * Tuples with multiple components
     *
     * @test
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider tupleWithMultipleComponents
     */
    public function testMultipleComponents($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for tuples with multiple components
     */
    public function tupleWithMultipleComponents() {
        $cassandraTypes = array_merge($this->scalarCassandraTypes(), $this->compositeCassandraTypes());
        $sizes = range(2, count($cassandraTypes));
        return array_map(function ($size) use ($cassandraTypes) {
            $types = array();
            for ($i = 0; $i < $size; $i++) {
                $types[] = $cassandraTypes[$i][0];
            }
            $tuple = new Tuple($types);
            for ($i = 0; $i < $size; $i++) {
                $tuple->set($i, $cassandraTypes[$i][1][0]);
            }
            return array($tuple->type(), $tuple);
        }, $sizes);
    }

    /**
     * Bind statment with a empty tuples
     *
     * @test
     * @ticket PHP-57
     * @ticket PHP-58
     * @dataProvider tupleWithMultipleEmptyComponents
     */
    public function testEmpty($type, $value) {
        $this->createTableInsertAndVerifyValueByIndex($type, $value);
        $this->createTableInsertAndVerifyValueByName($type, $value);
    }

    /**
     * Data provider for tuples with multiple components and no values
     */
    public function tupleWithMultipleEmptyComponents() {
        $scalarCassandraTypes = $this->scalarCassandraTypes();
        $sizes = range(2, count($scalarCassandraTypes));
        return array_map(function ($size) use ($scalarCassandraTypes) {
            $types = array();
            for ($i = 0; $i < $size; $i++) {
                $types[] = $scalarCassandraTypes[$i][0];
            }
            $tuple = new Tuple($types);
            return array($tuple->type(), $tuple);
        }, $sizes);
    }

    /**
     * Bind statement with an null tuple
     *
     * @test
     * @ticket PHP-58
     */
    public function testNull() {
        $tupleType = Type::tuple(Type::int());
        $this->createTableInsertAndVerifyValueByIndex($tupleType, null);
        $this->createTableInsertAndVerifyValueByName($tupleType, null);
    }

    /**
     * Partial tuple
     *
     * This test will ensure that partial tuples return the correct value.
     *
     * @test
     * @ticket PHP-58
     */
    public function testPartial() {
        $tupleType = Type::tuple(Type::int(), Type::varchar(), Type::bigint());

        $tuple = $tupleType->create();
        $tuple->set(0, 99);
        $this->createTableInsertAndVerifyValueByIndex($tupleType, $tuple);

        $tuple = $tupleType->create();
        $tuple->set(1, "abc");
        $this->createTableInsertAndVerifyValueByIndex($tupleType, $tuple);

        $tuple = $tupleType->create();
        $tuple->set(2, new Bigint("999999999999"));
        $this->createTableInsertAndVerifyValueByIndex($tupleType, $tuple);
    }

    /**
     * Invalid datatypes for tuples.
     *
     * This test will ensure that an exception will occur when an invalid
     * datatype is used inside a tuple; issues from the server.
     *
     * @test
     * @ticket PHP-58
     * @expectedException \Cassandra\Exception\InvalidQueryException
     */
    public function testInvalidType() {
        $validType = Type::tuple(Type::int());
        $invalidType = Type::tuple(Type::varchar());

        $tableName = $this->createTable($validType);

        $options = new ExecutionOptions(array(
            'arguments' => array("key", $invalidType->create("value")))
        );

        $this->insertValue($tableName, $options);
    }

    /**
     * Tuple using a nested user type.
     *
     * This test will ensure that the PHP driver supports the tuples collection
     * with user types.
     *
     * @test
     * @ticket PHP-57
     * @ticket PHP-58
     */
    public function testUserType() {
        // Create the user types
        $this->session->execute(new SimpleStatement(UserTypeIntegrationTest::PHONE_USER_TYPE_CQL));
        $this->session->execute(new SimpleStatement(UserTypeIntegrationTest::ADDRESS_USER_TYPE_CQL));

        // Create the table
        $query = "CREATE TABLE " . $this->tableNamePrefix .
            " (key timeuuid PRIMARY KEY, value " .
            "frozen<tuple<address>>)";
        $this->session->execute(new SimpleStatement($query));

        // Generate a valid address user type and assign it to a tuple
        $address = UserTypeIntegrationTest::generateAddressValue();
        $tuple = new Tuple(array($address->type()));
        $tuple->set(0, $address);

        // Assign the values for the statement
        $key = new Timeuuid();
        $values = array(
            $key,
            $tuple
        );

        // Insert the value into the table
        $query = "INSERT INTO " . $this->tableNamePrefix . " (key, value) VALUES (?, ?)";
        $statement = new SimpleStatement($query);
        $options = new ExecutionOptions(array("arguments" => $values));
        $this->session->execute($statement, $options);

        // Select the tuple
        $query = "SELECT value FROM " . $this->tableNamePrefix . " WHERE key=?";
        $statement = new SimpleStatement($query);
        $options = new ExecutionOptions(array("arguments" => array($key)));
        $rows = $this->session->execute($statement, $options);

        // Ensure the tuple collection is valid
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertNotNull($row);
        $this->assertArrayHasKey("value", $row);
        $tuple = $row["value"];
        $this->assertInstanceOf('Cassandra\Tuple', $tuple);
        $this->assertCount(1, $tuple);

        // Verify the value can be read from the table
        UserTypeIntegrationTest::assertAddressValue($tuple->get(0));
    }
}
