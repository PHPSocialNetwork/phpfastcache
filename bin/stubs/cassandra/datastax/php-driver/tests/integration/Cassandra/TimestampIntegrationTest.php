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
 * Timestamp integration tests.
 *
 * @cassandra-version-2.1
 */
class TimestampIntegrationTest extends BasicIntegrationTest {
    /**
     * Session connection configured for client side timestamps.
     *
     * @var \Cassandra\Session
     */
    private $clientSideTimestampSession;
    /**
     * Common insert query for timestamp tests.
     *
     * @var string
     */
    private $insertQuery;
    /**
     * Common select query for timestamp tests.
     *
     * @var string
     */
    private $selectQuery;

    /**
     * Create the table and client side timestamp session for the timestamp
     * tests.
     */
    public function setUp() {
        // Process parent setup steps
        parent::setUp();

        // Create the table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key int PRIMARY KEY, value_int int)";
        $this->session->execute(new SimpleStatement($query));

        // Generate the insert and select queries
        $this->insertQuery = "INSERT INTO {$this->tableNamePrefix} (key, value_int) VALUES (?, ?)";
        $this->selectQuery = "SELECT writeTime(value_int) FROM {$this->tableNamePrefix} WHERE key = ?";

        // Create the connection for client side timestamps
        $cluster = \Cassandra::cluster()
            ->withContactPoints(Integration::IP_ADDRESS)
            ->withTimestampGenerator(new TimestampGenerator\Monotonic())
            ->build();
        $this->clientSideTimestampSession = $cluster->connect($this->keyspaceName);
    }

    /**
     * Get the current time in microseconds.
     *
     * @return int Local unix time in microseconds
     */
    private function now() {
        return round(microtime(true) * self::SECONDS_TO_MICROSECONDS);
    }

    /**
     * Get the current server time in microseconds.
     *
     * @return int Server unix time in microseconds
     */
    private function serverNow() {
        // Query the server for the current time
        $query = "SELECT dateOf(now()) FROM system.local";
        $statement = new SimpleStatement($query);
        $rows = $this->session->execute($statement);

        // Return the server time in microseconds
        return current($rows->first())->time() * self::SECONDS_TO_MICROSECONDS;
    }

    /**
     * Insert a key/value into the table. If the key, value, or timestamp
     * is null it will not be added to the execution options for the statement.
     *
     * NOTE: For batch statement the key and value should be null.
     *
     * @param Session $session Session to use when executing statement
     * @param Statement $statement Statement to execute
     * @param int $key Key to insert value into (default: null)
     * @param int $value Value being set (default: null)
     * @param mixed $timestamp Timestamp to set in execution options
     *                         (default: null)
     */
    private function insert(Session $session, Statement $statement, $key = null, $value = null, $timestamp = null) {
        // Create the parameters that make up the execution options
        $parameters = array();

        // Determine if the key and/or value should be added as arguments
        if (isset($key) || isset($value)) {
            // Create the arguments array for the parameters
            $arguments = array();
            if (isset($key)) {
                $arguments["key"] = $key;
            }
            if (isset($value)) {
                $arguments["value_int"] = $value;
            }

            // Assign the arguments to the parameters
            $parameters["arguments"] = $arguments;
        }

        // Determine if the timestamp should be added
        if (isset($timestamp)) {
            $parameters["timestamp"] = $timestamp;
        }

        // Insert values into the table
        $options = new ExecutionOptions($parameters);
        $session->execute($statement, $options);
    }

    /**
     * Get the write time for a given key in the table.
     *
     * @param $key Key to determine `value_int` write time
     * @return int Write time for `value_int` in microseconds
     */
    private function getTimestamp($key) {
        // Select the timestamp from the table
        $statement = new SimpleStatement($this->selectQuery);
        $options = new ExecutionOptions(array(
            "arguments" => array(
                "key" => $key
            )
        ));
        $rows = $this->session->execute($statement, $options);
        $row = $rows->first();
        $this->assertArrayHasKey("writetime(value_int)", $row);

        // Return the timestamp
        return current($row)->value();
    }

    /**
     * Assert the timestamp for a given key in the table against the
     * `value_int`.
     *
     * @param $key Key to determine `value_int` write time
     * @param $expectedTimestamp Expected timestamp
     * @param bool $isEqual True is timestamp retrieved from server should
     *                      equal $exectedTimestamp; false to assume timestamp
     *                      is greater than expected
     */
    private function assert($key, $expectedTimestamp, $isEqual = true) {
        // Assert the timestamp
        $timestamp = $this->getTimestamp($key);
        if ($isEqual) {
            // Set timestamp via options or using should be equal
            $this->assertEquals($expectedTimestamp, $timestamp);
        } else {
            // Inserted timestamp (server or local) should be > expected
            $this->assertGreaterThan($expectedTimestamp, $timestamp);
        }
    }

    /**
     * Simple statement timestamps
     *
     * This test will ensure that the PHP driver supports timestamps using
     * simple statements; client, server, and forced (option and using)
     * timestamps are executed.
     *
     * @test
     * @ticket PHP-59
     *
     * @cassandra-version-2.1
     */
    public function testSimpleStatement() {
        // Using integer value (assigned timestamp)
        $statement = new SimpleStatement($this->insertQuery);
        $this->insert($this->session, $statement, 0, 1, 12345);
        $this->assert(0, 12345);

        // Using string value (assigned timestamp)
        $this->insert($this->session, $statement, 1, 2, 12345);
        $this->assert(1, 12345);

        // Using timestamp generator (client)
        $now = $this->now();
        sleep(1);
        $this->insert($this->clientSideTimestampSession, $statement, 2, 3);
        $this->assert(2, $now, false);

        // Using timestamp generator (server)
        $serverNow = $this->serverNow();
        sleep(1);
        $this->insert($this->session, $statement, 3, 4);
        $this->assert(3, $serverNow, false);

        // Using forced timestamp
        $query = "{$this->insertQuery} USING TIMESTAMP 30";
        $statement = new SimpleStatement($query);
        $this->insert($this->session, $statement, 4, 5, 12345);
        $this->assert(4, 30);
    }

    /**
     * Prepared statement timestamps
     *
     * This test will ensure that the PHP driver supports timestamps using
     * prepared statements; client, server, and forced (option and using)
     * timestamps are executed.
     *
     * @test
     * @ticket PHP-59
     *
     * @cassandra-version-2.1
     */
    public function testPreparedStatement() {
        // Using integer value
        $statement = $this->session->prepare($this->insertQuery);
        $this->insert($this->session, $statement, 0, 1, 54321);
        $this->assert(0, 54321);

        // Using string value
        $this->insert($this->session, $statement, 1, 2, "54321");
        $this->assert(1, 54321);

        // Using timestamp generator (client)
        $now = $this->now();
        sleep(1);
        $this->insert($this->clientSideTimestampSession, $statement, 2, 3);
        $this->assert(2, $now, false);

        // Using timestamp generator (server)
        $serverNow = $this->serverNow();
        sleep(1);
        $this->insert($this->session, $statement, 3, 4);
        $this->assert(3, $serverNow, false);

        // Using forced timestamp
        $query = "{$this->insertQuery} USING TIMESTAMP 60";
        $statement = $this->session->prepare($query);
        $this->insert($this->session, $statement, 4, 5, 54321);
        $this->assert(4, 60);
    }

    /**
     * Batch statement timestamps
     *
     * This test will ensure that the PHP driver supports timestamps using
     * batch statements; client, server, and forced (option and using)
     * timestamps are executed.
     *
     * @test
     * @ticket PHP-59
     *
     * @cassandra-version-2.1
     */
    public function testBatchStatement() {
        // Create the batch statement
        $batch = new BatchStatement(\Cassandra::BATCH_UNLOGGED);
        $simple = new SimpleStatement($this->insertQuery);
        $prepare = $this->session->prepare($this->insertQuery);

        // Simple statement
        $batch->add($simple, array(
            0,
            1
        ));
        // Prepared statement
        $batch->add($prepare, array(
            "key" => 1,
            "value_int" => 2
        ));
        // Forced timestamp (simple)
        $query = "{$this->insertQuery} USING TIMESTAMP 90";
        $simple = new SimpleStatement($query);
        $batch->add($simple, array(
            2,
            3
        ));

        // Insert the batch and assert the values
        $this->insert($this->session, $batch, null, null, 11111);
        $this->assert(0, 11111);
        $this->assert(1, 11111);
        $this->assert(2, 90);

        // Using timestamp generator (client); upsert will occur
        $now = $this->now();
        sleep(1);
        $this->insert($this->clientSideTimestampSession, $batch);
        $this->assert(0, $now, false);
        $this->assert(1, $now, false);
        $this->assert(2, 90);

        // Using timestamp generator (server); upsert will occur
        $serverMow = $this->serverNow();
        sleep(1);
        $this->insert($this->session, $batch);
        $this->assert(0, $serverMow, false);
        $this->assert(1, $serverMow, false);
        $this->assert(2, 90);
    }
}