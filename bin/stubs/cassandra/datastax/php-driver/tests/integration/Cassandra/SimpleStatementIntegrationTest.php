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
 * Simple statement integration tests.
 */
class SimpleStatementIntegrationTest extends BasicIntegrationTest {
    /**
     * Simple statements support named parameters for queries.
     *
     * This test will ensure that the PHP driver supports named parameters
     * using simple statement queries.
     *
     * @test
     * @ticket PHP-67
     *
     * @cassandra-version-2.1
     */
    public function testByName() {
        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key timeuuid, value_int int, value_boolean boolean, value_text text, PRIMARY KEY (value_int, key)) WITH CLUSTERING ORDER BY (key DESC)";
        $this->session->execute(new SimpleStatement($query));

        // Create the insert query and insert valid named parameters
        $query = "INSERT INTO {$this->tableNamePrefix} (key, value_int, value_boolean, value_text) VALUES (?, ?, ?, ?)";
        $values = array(
            // Reversed order from table and insert queries
            array(
                "value_text" => "This is row number one.",
                "value_boolean" => true,
                "value_int" => 1,
                "key" => new Timeuuid()
            ),

            // Random order
            array(
                "value_int" => 2,
                "key" => new Timeuuid(),
                "value_boolean" => false,
                "value_text" => "This is row number two."
            ),

            // In order
            array(
                "key" => new Timeuuid(),
                "value_int" => 3,
                "value_boolean" => false,
                "value_text" => "This is row number three."
            )
        );
        $statement = new SimpleStatement($query);
        foreach ($values as $value) {
            $options = new ExecutionOptions(array("arguments" => $value));
            $this->session->execute($statement, $options);
        }

        // Select and assert the values
        $query = "SELECT * FROM {$this->tableNamePrefix}";
        $statement = new SimpleStatement($query);
        $rows = $this->session->execute($statement);
        $this->assertCount(count($values), $rows);
        foreach ($rows as $i => $row) {
            $value = $values[$i];
            $this->assertEquals($value, $row);
            $this->assertTrue($value == $row);
            $this->assertEquals($value["key"], $row["key"]);
            $this->assertEquals($value["value_int"], $row["value_int"]);
            $this->assertEquals($value["value_boolean"], $row["value_boolean"]);
            $this->assertEquals($value["value_text"], $row["value_text"]);
            $this->assertTrue($value["key"] == $row["key"]);
            $this->assertTrue($value["value_int"] == $row["value_int"]);
            $this->assertTrue($value["value_boolean"] == $row["value_boolean"]);
            $this->assertTrue($value["value_text"] == $row["value_text"]);
        }
    }

    /**
     * Simple statements support case sensitive named parameters for queries.
     *
     * This test will ensure that the PHP driver supports case sensitive named
     * parameters using simple statement queries.
     *
     * @test
     * @ticket PHP-67
     *
     * @cassandra-version-2.1
     * @cpp-driver-version-2.2.3
     *
     * NOTE: Test is not skipped when using C/C++ driver v2.2.3+
     */
    public function testCaseSensitiveByName() {
        // Determine if the test should be skipped
        if (version_compare(\Cassandra::CPP_DRIVER_VERSION, "2.2.3") < 0) {
            $this->markTestSkipped("Skipping {$this->getName()}: Case sensitivity issue fixed in DataStax C/C++ v 2.2.3");
        }

        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key timeuuid, value_int int, \"value_iNT\" int, value_boolean boolean, \"value_BooLeaN\" boolean, PRIMARY KEY (value_int, key)) WITH CLUSTERING ORDER BY (key DESC)";
        $this->session->execute(new SimpleStatement($query));

        // Create the insert query and insert valid named parameters
        $query = "INSERT INTO {$this->tableNamePrefix} (key, value_int, \"value_iNT\", value_boolean, \"value_BooLeaN\") VALUES (?, ?, ?, ?, ?)";
        $values = array(
            // Reversed order from table and insert queries
            array(
                "\"value_BooLeaN\"" => false,
                "\"value_boolean\"" => true,
                "\"value_iNT\"" => 11,
                "\"value_int\"" => 1,
                "key" => new Timeuuid()
            ),

            // Random order
            array(
                "\"value_int\"" => 2,
                "\"value_BooLeaN\"" => true,
                "key" => new Timeuuid(),
                "\"value_boolean\"" => false,
                "\"value_iNT\"" => 22
            ),

            #// In order
            array(
                "key" => new Timeuuid(),
                "\"value_int\"" => 3,
                "\"value_iNT\"" => 33,
                "\"value_boolean\"" => false,
                "\"value_BooLeaN\"" => true
            )
        );
        $statement = new SimpleStatement($query);
        foreach ($values as $value) {
            $options = new ExecutionOptions(array("arguments" => $value));
            $this->session->execute($statement, $options);
        }

        // Select and assert the values
        $query = "SELECT * FROM {$this->tableNamePrefix}";
        $statement = new SimpleStatement($query);
        $rows = $this->session->execute($statement);
        $this->assertCount(count($values), $rows);
        foreach ($rows as $i => $row) {
            $expected = array();
            foreach ($values[$i] as $key => $value) {
                $expected[trim($key, "\"")] = $value;
            }
            $this->assertEquals($expected, $row);
        }
    }

    /**
     * Simple statements throw exception using invalid arguments/bind name.
     *
     * This test will ensure that the PHP driver throws an excretion when
     * attempting to bind a value using an invalid name.
     *
     * @test
     * @ticket PHP-67
     *
     * @cassandra-version-2.1
     *
     * @expectedException \Cassandra\Exception\InvalidQueryException
     * @expectedExceptionMessage Invalid amount of bind variables
     */
    public function testByNameInvalidBindName() {
        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key timeuuid PRIMARY KEY, value_int int)";
        $this->session->execute(new SimpleStatement($query));

        // Create the insert query and attempt to insert invalid valid name
        $query = "INSERT INTO {$this->tableNamePrefix} (key, value_int) VALUES (?, ?)";
        $values = array(
            "key" => new Timeuuid(),
            "wrong_name" => 1
        );
        $statement = new SimpleStatement($query);
        $options = new ExecutionOptions(array("arguments" => $values));
        $this->session->execute($statement, $options);
    }

    /**
     * Simple statements throw exception using invalid arguments/bind name.
     *
     * This test will ensure that the PHP driver throws an excretion when
     * attempting to bind a value using an invalid name; case-sensitive.
     *
     * @test
     * @ticket PHP-67
     *
     * @cassandra-version-2.1
     * @cpp-driver-version-2.2.3
     *
     * @expectedException \Cassandra\Exception\InvalidQueryException
     * @expectedExceptionMessage Invalid amount of bind variables
     */
    public function testCaseSensitiveByNameInvalidBindName() {
        // Determine if the test should be skipped
        if (version_compare(\Cassandra::CPP_DRIVER_VERSION, "2.2.3") < 0) {
            $this->markTestSkipped("Skipping {$this->getName()}: Case sensitivity issue fixed in DataStax C/C++ v 2.2.3");
        }

        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key timeuuid PRIMARY KEY, \"value_iNT\" int, \"value_TeXT\" text)";
        $this->session->execute(new SimpleStatement($query));

        // Create the insert query and attempt to insert invalid valid name
        $query = "INSERT INTO {$this->tableNamePrefix} (key, \"value_TeXT\", \"value_iNT\") VALUES (?, ?, ?)";
        $values = array(
            "key" => new Timeuuid(),
            "value_iNT" => 1,
            "value_text" => "Exception will be thrown; case-sensitive"
        );
        $statement = new SimpleStatement($query);
        $options = new ExecutionOptions(array("arguments" => $values));
        $this->session->execute($statement, $options);
    }

    /**
     * Simple statements support null values when using named parameters.
     *
     * This test will ensure that the PHP driver supports named parameters
     * using simple statement queries with null values.
     *
     * @test
     * @ticket PHP-67
     *
     * @cassandra-version-2.1
     */
    public function testByNameNullValue() {
        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key timeuuid PRIMARY KEY, value_int int, value_boolean boolean, value_text text)";
        $this->session->execute(new SimpleStatement($query));

        // Create the insert query and insert valid named parameters
        $query = "INSERT INTO {$this->tableNamePrefix} (key, value_int, value_boolean, value_text) VALUES (?, ?, ?, ?)";
        $values = array(
            "key" => new Timeuuid(),
            "value_int" => null,
            "value_boolean" => null,
            "value_text" => "Null values should exist for value_int and value_boolean"
        );
        $statement = new SimpleStatement($query);
        $options = new ExecutionOptions(array("arguments" => $values));
        $this->session->execute($statement, $options);

        // Select and assert the values
        $query = "SELECT * FROM {$this->tableNamePrefix}";
        $statement = new SimpleStatement($query);
        $rows = $this->session->execute($statement);
        $this->assertCount(1, $rows);
        $row = $rows->first();
        $this->assertEquals($values, $row);
        $this->assertTrue($values == $row);
        $this->assertEquals($values["key"], $row["key"]);
        $this->assertEquals($values["value_int"], $row["value_int"]);
        $this->assertEquals($values["value_boolean"], $row["value_boolean"]);
        $this->assertEquals($values["value_text"], $row["value_text"]);
        $this->assertTrue($values["key"] == $row["key"]);
        $this->assertTrue($values["value_int"] == $row["value_int"]);
        $this->assertTrue($values["value_boolean"] == $row["value_boolean"]);
        $this->assertTrue($values["value_text"] == $row["value_text"]);
        $this->assertNull($row["value_int"]);
        $this->assertNull($row["value_boolean"]);
    }
}
