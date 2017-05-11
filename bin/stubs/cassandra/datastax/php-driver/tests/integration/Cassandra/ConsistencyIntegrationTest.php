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
 * Consistency level integration tests.
 */
class ConsistencyIntegrationTest extends BasicIntegrationTest {
    /**
     * Default consistency level (string value)
     */
    const DEFAULT_CONSISTENCY_LEVEL = "LOCAL_ONE";

    /**
     * Default consistency level is used for executed statements.
     *
     * This test will ensure that the PHP driver uses the default consistency
     * level for all executed statement types.
     *
     * @test
     * @ticket PHP-49
     */
    public function testDefaultConsistencyLevel() {
        // Create a new table
        $query = "CREATE TABLE {$this->tableNamePrefix} (key int PRIMARY KEY)";
        $statement = new SimpleStatement($query);
        $this->session->execute($statement);

        // Enable tracing
        $this->ccm->enableTracing(true);

        // Insert a value into the table
        $insertQuery = "INSERT INTO {$this->tableNamePrefix} (key) VALUES (1)";
        $statement = new SimpleStatement($insertQuery);
        $this->session->execute($statement);

        // Check the trace logs to determine the consistency level used
        $query = "SELECT parameters FROM system_traces.sessions";
        $statement = new SimpleStatement($query);
        $rows = $this->session->execute($statement);
        $isAsserted = false;
        foreach ($rows as $row) {
            // Find the parameters that contains the insert query
            $parameters = $row["parameters"];
            $query = $parameters["query"];
            if ($query == $insertQuery) {
                $consistency = $parameters["consistency_level"];
                $this->assertEquals(self::DEFAULT_CONSISTENCY_LEVEL, $consistency);
                $isAsserted = true;
                break;
            }
        }

        // Ensure that an assertion was made (e.g. consistency level used)
        $this->assertTrue($isAsserted);
    }
}
