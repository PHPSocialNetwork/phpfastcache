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
 * Schema metadata integration tests.
 */
class SchemaMetadataIntegrationTest extends BasicIntegrationTest {
    /**
     * Schema snapshot associated with the $this->session connection.
     *
     * @var \Cassandra\Schema
     */
    private $schema;

    /**
     * Setup the schema metadata for the schema metadata tests.
     */
    public function setUp() {
        // Determine if UDA/UDF functionality should be enabled
        $testName = $this->getName();
        if (strpos($testName, "UserDefined") !== false) {
            $this->isUserDefinedAggregatesFunctions = true;
        }

        // Process parent setup steps
        parent::setUp();

        // Initialize the session schema metadata snapshot
        $this->schema = $this->session->schema();
    }

    protected static function generateKeyspaceName($prefix) {
        return substr(uniqid($prefix), 0, 48);
    }

    protected function createKeyspace($keyspaceName, $replicationFactor = 1) {
        $statement = new SimpleStatement(
            "CREATE KEYSPACE $keyspaceName " .
            "WITH REPLICATION = { 'class' : 'SimpleStrategy', 'replication_factor' : $replicationFactor }"
        );
        $this->session->execute($statement);
    }

    protected function createKeyspaceWithSchema($keyspaceName, $tableSchemas) {
        $this->createKeyspace($keyspaceName);
        foreach ($tableSchemas as $tableName => $tableSchema) {
            $query = sprintf("CREATE TABLE $keyspaceName.$tableName (%s, PRIMARY KEY(%s))",
                implode(", ",
                    array_map(function ($key, $value) { return "$key $value"; },
                    array_keys($tableSchema), array_values($tableSchema))),
                implode(", ",
                    array_filter(array_keys($tableSchema),
                    function ($columnName) { return strpos($columnName, "key") === 0; }))
            );
            $this->session->execute(new SimpleStatement($query));
        }
    }

    /**
     * Create the table for the secondary indexes
     */
    protected function createTableForSecondaryIndexes() {
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix} (key1 text, value1 int, value2 map<text, text>, PRIMARY KEY(key1))"
        );
        $this->session->execute($statement);
    }

    /**
     * Create the simple secondary index using the table
     */
    protected function createSimpleSecondaryIndex() {
        $statement = new SimpleStatement(
            "CREATE INDEX simple ON {$this->tableNamePrefix} (value1)"
        );
        $this->session->execute($statement);
    }

    /**
     * Create the collections secondary index using the table
     */
    protected function createCollectionSecondaryIndex() {
        $statement = new SimpleStatement(
            "CREATE INDEX collection ON {$this->tableNamePrefix} (KEYS(value2))"
        );
        $this->session->execute($statement);
    }

    /**
     * Assert the index instance
     *
     * @param $index Index to assert against
     * @param $kind Kind to assert
     * @param $target Target (and index->option('target')) to assert for index->target()
     * @param $className Boolean or string value to assert for index->className
     * @param $isCustom Value of index->isCustom() to assert
     */
    protected function assertIndex($index, $kind, $target, $className, $isCustom) {
        $this->assertEquals($kind, $index->kind());
        $this->assertEquals($target, $index->target());
        $this->assertEquals($isCustom, $index->isCustom());
        if ($isCustom) {
            $this->assertEquals($className, $index->className());
        } else {
            $this->assertFalse($index->className());
        }
        if (version_compare($this->serverVersion, "3.0.0", ">=")) {
            $this->assertCount(1, $index->options());
            $this->assertEquals($target, $index->option("target"));
        }
    }

    /**
     * Create the tables for the materialized views
     */
    protected function createTablesForMaterializedViews() {
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_1 (key1 text, value1 int, PRIMARY KEY(key1))"
        );
        $this->session->execute($statement);
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_2 (key1 text, key2 int, value1 int, PRIMARY KEY(key1, key2))"
        );
        $this->session->execute($statement);
    }

    /**
     * Create the simple materialized view using the first table
     */
    protected function createSimpleMaterializedView() {
        $statement = new SimpleStatement(
            "CREATE MATERIALIZED VIEW simple AS " .
            "SELECT key1 FROM {$this->tableNamePrefix}_1 WHERE value1 IS NOT NULL " .
            "PRIMARY KEY(value1, key1)"
        );
        $this->session->execute($statement);
    }

    /**
     * Create the primary key materialized view using the second table
     */
    protected function createPrimaryKeyMaterializedView() {
        $statement = new SimpleStatement(
            "CREATE MATERIALIZED VIEW primary_key AS " .
            "SELECT key1 FROM {$this->tableNamePrefix}_2 WHERE key2 IS NOT NULL AND value1 IS NOT NULL " .
            "PRIMARY KEY((value1, key2), key1)"
        );
        $this->session->execute($statement);
    }

    /**
     * Create the primary key materialized view using the second table
     */
    protected function createClusteringKeyMaterializedView() {
        $statement = new SimpleStatement(
            "CREATE MATERIALIZED VIEW clustering_key AS " .
            "SELECT key1 FROM {$this->tableNamePrefix}_2 WHERE key2 IS NOT NULL AND value1 IS NOT NULL " .
            "PRIMARY KEY(value1, key2, key1) " .
            "WITH CLUSTERING ORDER BY (key2 DESC)"
        );
        $this->session->execute($statement);
    }

    /**
     * Assert the materialized views are equal
     *
     * @param $materializedViewOne First materialized view
     * @param $materializedViewTwo Second materialized view
     */
    protected function assertMaterializedViewsEqual($materializedViewOne, $materializedViewTwo) {
        $this->assertEquals($materializedViewOne->name(), $materializedViewTwo->name());
        $this->assertEquals($materializedViewOne->options(), $materializedViewTwo->options());
        $this->assertEquals($materializedViewOne->comment(), $materializedViewTwo->comment());
        $this->assertEquals($materializedViewOne->readRepairChance(), $materializedViewTwo->readRepairChance());
        $this->assertEquals($materializedViewOne->localReadRepairChance(), $materializedViewTwo->localReadRepairChance());
        $this->assertEquals($materializedViewOne->gcGraceSeconds(), $materializedViewTwo->gcGraceSeconds());
        $this->assertEquals($materializedViewOne->caching(), $materializedViewTwo->caching());
        $this->assertEquals($materializedViewOne->bloomFilterFPChance(), $materializedViewTwo->bloomFilterFPChance());
        $this->assertEquals($materializedViewOne->memtableFlushPeriodMs(), $materializedViewTwo->memtableFlushPeriodMs());
        $this->assertEquals($materializedViewOne->defaultTTL(), $materializedViewTwo->defaultTTL());
        $this->assertEquals($materializedViewOne->speculativeRetry(), $materializedViewTwo->speculativeRetry());
        $this->assertEquals($materializedViewOne->indexInterval(), $materializedViewTwo->indexInterval());
        $this->assertEquals($materializedViewOne->compactionStrategyClassName(), $materializedViewTwo->compactionStrategyClassName());
        $this->assertEquals($materializedViewOne->compactionStrategyOptions(), $materializedViewTwo->compactionStrategyOptions());
        $this->assertEquals($materializedViewOne->compressionParameters(), $materializedViewTwo->compressionParameters());
        $this->assertEquals($materializedViewOne->populateIOCacheOnFlush(), $materializedViewTwo->populateIOCacheOnFlush());
        $this->assertEquals($materializedViewOne->replicateOnWrite(), $materializedViewTwo->replicateOnWrite());
        $this->assertEquals($materializedViewOne->maxIndexInterval(), $materializedViewTwo->maxIndexInterval());
        $this->assertEquals($materializedViewOne->minIndexInterval(), $materializedViewTwo->minIndexInterval());
        $this->assertEquals($materializedViewOne->columns(), $materializedViewTwo->columns());
        $this->assertEquals($materializedViewOne->partitionKey(), $materializedViewTwo->partitionKey());
        $this->assertEquals($materializedViewOne->primaryKey(), $materializedViewTwo->primaryKey());
        $this->assertEquals($materializedViewOne->clusteringKey(), $materializedViewTwo->clusteringKey());
        $this->assertEquals($materializedViewOne->clusteringOrder(), $materializedViewTwo->clusteringOrder());
        $this->assertEquals($materializedViewOne->baseTable(), $materializedViewTwo->baseTable());
    }

    /**
     * Assert the materialized view (not all elements)
     *
     * @param $materializedView Materialized view to validate
     * @param $name Name to assert
     * @param $tableName Table name to assert
     * @param $columns Columns names to assert
     * @param $partitionKeyColumns Primary key column names to assert
     * @param $clusteringKeyColumns Cluster key column names to assert
     */
    protected function assertMaterializedView($materializedView, $name, $tableName, $columns, $partitionKeyColumns, $clusteringKeyColumns) {
        $this->assertEquals($materializedView->name(), $name);
        $this->assertEquals($materializedView->baseTable()->name(), $tableName);
        $i = 0;
        foreach ($materializedView->columns() as $column) {
            $this->assertEquals($columns[$i++], $column->name());
        }
        $i = 0;
        foreach ($materializedView->partitionKey() as $column) {
            $this->assertEquals($partitionKeyColumns[$i++], $column->name());
        }
        $i = 0;
        foreach ($materializedView->clusteringKey() as $column) {
            $this->assertEquals($clusteringKeyColumns[$i++], $column->name());
        }
        $primaryKeyColumns = array_merge($partitionKeyColumns, $clusteringKeyColumns);
        $i = 0;
        foreach ($materializedView->primaryKey() as $column) {
            $this->assertEquals($primaryKeyColumns[$i++], $column->name());
        }
    }

    /**
     * Create the user defined function
     */
    protected function createUserDefinedFunction() {
        $statement = new SimpleStatement(
          "CREATE OR REPLACE FUNCTION user_defined_function(rhs int, lhs int) " .
          "RETURNS NULL ON NULL INPUT " .
          "RETURNS int LANGUAGE javascript AS 'lhs + rhs'"
        );
        $this->session->execute($statement);

        $cluster = \Cassandra::cluster()
            ->withContactPoints("127.0.0.1")
            ->withPersistentSessions(false)
            ->build();
        $session = $cluster->connect();
    }

    /**
     * Assert the `user_defined_function` function
     */
    protected function assertUserDefinedFunction() {
        // Get the UDF from the current keyspace
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $function = $keyspace->function("user_defined_function",
            Type::int(), Type::int());
        $expectedArguments = array(
            "rhs" => "int",
            "lhs" => "int"
        );

        // Assert the UDF
        $this->assertEquals("user_defined_function", $function->simpleName());
        $arguments = array();
        foreach ($function->arguments() as $name => $argument) {
            $arguments[$name] = "{$argument}";
        }
        $this->assertEquals($expectedArguments, $arguments);
        $this->assertEquals("int", $function->returnType());
        $this->assertEquals("user_defined_function(int,int)", $function->signature());
        $this->assertEquals("javascript", $function->language());
        $this->assertEquals("lhs + rhs", $function->body());
        $this->assertEquals(false, $function->isCalledOnNullInput());
    }

    /**
     * Assert the UDFs are equal
     *
     * @param $name Name of user defined function to look up and compare
     * @param $function User defined function to compare
     */
    protected function assertUserDefinedFunctionsEqual($name, $function) {
        // Get the UDF from the current keyspace
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $expectedFunction = null;
        foreach ($keyspace->functions() as $compareFunction) {
            if ($compareFunction->simpleName() == $name) {
                $expectedFunction = $compareFunction;
                continue;
            }
        }
        if (is_null($expectedFunction)) {
            $this->fail("Unable to Locate Function: ${name}");
        }

        $this->assertEquals($expectedFunction->simpleName(),
            $function->simpleName());
        $this->assertEquals($expectedFunction->arguments(),
            $function->arguments());
        $this->assertEquals($expectedFunction->returnType(),
            $function->returnType());
        $this->assertEquals($expectedFunction->signature(),
            $function->signature());
        $this->assertEquals($expectedFunction->language(),
            $function->language());
        $this->assertEquals($expectedFunction->body(),
            $function->body());
        $this->assertEquals($expectedFunction->isCalledOnNullInput(),
            $function->isCalledOnNullInput());
    }

    /**
     * Create the user defined aggregate and two user defined functions for the
     * associated aggregate
     */
    protected function createUserDefinedAggregate() {
        // Ensure the UDF has been created
        $this->createUserDefinedFunction();

        // Create the UDA
        $statement = new SimpleStatement(
            "CREATE OR REPLACE FUNCTION uda_udf_final(val int) " .
            "RETURNS NULL ON NULL INPUT " .
            "RETURNS int LANGUAGE javascript AS 'val * val'"
        );
        $this->session->execute($statement);
        $statement = new SimpleStatement(
            "CREATE OR REPLACE AGGREGATE user_defined_aggregate(int) " .
            "SFUNC user_defined_function " .
            "STYPE int " .
            "FINALFUNC uda_udf_final " .
            "INITCOND 0"
        );
        $this->session->execute($statement);
    }

    /**
     * Assert the `uda_udf_final` function
     */
    protected function assertAggregateUserDefinedFunction() {
        // Get the UDF from the current keyspace
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $function = $keyspace->function("uda_udf_final", Type::int());
        $expectedArguments = array(
            "val" => "int"
        );

        // Assert the UDF
        $this->assertEquals("uda_udf_final", $function->simpleName());
        $arguments = array();
        foreach ($function->arguments() as $name => $argument) {
            $arguments[$name] = "{$argument}";
        }
        $this->assertEquals($expectedArguments, $arguments);
        $this->assertEquals("int", $function->returnType());
        $this->assertEquals("uda_udf_final(int)", $function->signature());
        $this->assertEquals("javascript", $function->language());
        $this->assertEquals("val * val", $function->body());
        $this->assertEquals(false, $function->isCalledOnNullInput());
    }

    /**
     * Assert the `user_defined_aggregate` aggregate
     */
    protected function assertUserDefinedAggregate() {
        // Get the UDA from the current keyspace
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $function = $keyspace->aggregate("user_defined_aggregate", Type::int());
        $expectedArgumentTypes = array("int");

        // Assert the UDA
        $this->assertEquals("user_defined_aggregate", $function->simpleName());
        $argumentTypes = array();
        foreach ($function->argumentTypes() as $argumentType) {
            $argumentTypes[] = "{$argumentType}";
        }
        $this->assertEquals($expectedArgumentTypes, $argumentTypes);
        $this->assertUserDefinedFunctionsEqual("user_defined_function",
            $function->stateFunction());
        $this->assertUserDefinedFunctionsEqual("uda_udf_final",
            $function->finalFunction());
        $this->assertEquals(0, $function->initialCondition());
        $this->assertEquals("int", $function->stateType());
        $this->assertEquals("int", $function->returnType());
        $this->assertEquals("user_defined_aggregate(int)", $function->signature());

        // Assert the UDFs
        $this->assertUserDefinedFunction();
        $this->assertAggregateUserDefinedFunction();
    }

    /**
     * Schema metadata support is available; basic test.
     *
     * This test will ensure that the PHP driver supports schema metadata.
     */
    public function testBasicSchemaMetadata() {
        // Ensure the test class session connection has schema metadata
        $this->assertGreaterThan(0, count($this->schema));

        // Ensure the test class session contains the test keyspace
        $this->assertArrayHasKey($this->keyspaceName, $this->schema->keyspaces());
        $keyspace = $this->schema->keyspace($this->keyspaceName);
    }

    /**
     * Schema metadata support can be disabled.
     *
     * This test will ensure that the PHP driver supports the ability to enable
     * and disable the schema metadata when creating a session object.
     *
     * @test
     * @ticket PHP-61
     */
    public function testDisableSchemaMetadata() {
        // Create a new session with schema metadata disabled
        $cluster = \Cassandra::cluster()
            ->withContactPoints(Integration::IP_ADDRESS)//TODO: Need to use configured value when support added
            ->withSchemaMetadata(false)
            ->build();
        $session = $cluster->connect();

        // Get the schema from the new session
        $schema = $session->schema();

        // Ensure the new session has no schema metadata
        $this->assertCount(0, $schema->keyspaces());
        $this->assertNotEquals($this->schema->keyspaces(), $schema->keyspaces());
    }

    /**
     * Enumerate over keyspaces in schema metadata.
     *
     * This test ensures that driver correctly enumerates over keyspace
     * metadata.
     *
     * @test
     */
    public function testEnumerateKeyspaces() {
        $keyspaceNames = array(
            self::generateKeyspaceName("enumerate_ks0"),
            self::generateKeyspaceName("enumerate_ks1"),
            self::generateKeyspaceName("enumerate_ks2"),
            "system",
        );

        foreach ($keyspaceNames as $keyspaceName) {
            if (strpos($keyspaceName, "system") === 0) continue;
            $this->createKeyspace($keyspaceName);
        }

        $count = 0;
        foreach ($this->session->schema()->keyspaces() as $keyspace) {
            if (in_array($keyspace->name(), $keyspaceNames)) {
                $count++;
            }
        }

        $this->assertEquals($count, count($keyspaceNames));
    }

    /**
     * Get keyspace from schema metadata using keyspace name.
     *
     * This test ensures that the driver is able to access keyspace metadata by
     * name.
     *
     * @test
     */
    public function testGetKeyspaceByName() {
        $keyspaceNames = array(
            self::generateKeyspaceName("by_name_ks0"),
            self::generateKeyspaceName("by_name_ks1"),
            self::generateKeyspaceName("by_name_ks2"),
            "system",
        );

        foreach ($keyspaceNames as $keyspaceName) {
            if (strpos($keyspaceName, "system") === 0) continue;
            $this->createKeyspace($keyspaceName);
        }

        $count = 0;
        foreach ($keyspaceNames as $keyspaceName) {
            $keyspace = $this->session->schema()->keyspace($keyspaceName);
            if (isset($keyspace)) {
                $count++;
            }
        }

        $this->assertEquals($count, count($keyspaceNames));
    }

    /**
     * Enumerate over tables and columns in schema metadata.
     *
     * This test ensures that driver correctly enumerates over table and column
     * metadata.
     *
     * @todo: Add "varchar" and "text" tests
     *
     * @test
     */
    public function testEnumerateTablesAndColumns() {
        $keyspaceName = self::generateKeyspaceName("enumerate");

        $tableSchemas = array(
            "table_int_int" => array("key" => "int", "value" => "int"),
            "table_int_bigint" => array("key" => "int", "value" => "bigint"),
            "table_decimal_map" => array("key" => "decimal", "value" => "map<bigint, uuid>")
        );

        $this->createKeyspaceWithSchema($keyspaceName, $tableSchemas);

        $keyspace = $this->session->schema()->keyspace($keyspaceName);

        $this->assertEquals(count($tableSchemas), count($keyspace->tables()));
        foreach ($keyspace->tables() as $table) {
            $tableSchema = $tableSchemas[$table->name()];
            $this->assertEquals(count($tableSchema), count($table->columns()));
            foreach ($table->columns() as $column) {
                $columnType = $tableSchema[$column->name()];
                $this->assertEquals($columnType, (string)$column->type());
            }
        }
    }

    /**
     * Get tables and columns from schema metadata using their names.
     *
     * This test ensures that the driver is able to access table and column
     * metadata by name.
     *
     * @todo: Add "varchar" and "text" tests
     *
     * @test
     */
    public function testGetTableAndColumnByName() {
        $keyspaceName = self::generateKeyspaceName("by_name");

        $tableSchemas = array(
            "table_int_int" => array("key" => "int", "value" => "int"),
            "table_int_bigint" => array("key" => "int", "value" => "bigint"),
            "table_decimal_map" => array("key" => "decimal", "value" => "map<bigint, uuid>")
        );

        $this->createKeyspaceWithSchema($keyspaceName, $tableSchemas);

        $keyspace = $this->session->schema()->keyspace($keyspaceName);

        $this->assertEquals(count($tableSchemas), count($keyspace->tables()));

        foreach ($tableSchemas as $tableName => $tableSchema) {
            $table = $keyspace->table($tableName);
            $this->assertEquals(count($tableSchema), count($table->columns()));
            foreach ($tableSchema as $columnName => $columnType) {
                $column = $table->column($columnName);
                $this->assertEquals($columnType, (string)$column->type());
            }
        }
    }

    /**
     * Get index options metadata from a column
     *
     * This test ensures that index options metadata are properly returned from
     * an indexed column.
     *
     * @test
     */
    public function testGetColumnIndexOptions() {
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_with_index (key int PRIMARY KEY, value map<text, frozen<map<int, int>>>)"
        );
        $this->session->execute($statement);

        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertNotNull($keyspace);

        $table = $keyspace->table("{$this->tableNamePrefix}_with_index");
        $this->assertNotNull($table);

        $indexOptions = $table->column("value")->indexOptions();
        $this->assertNull($indexOptions);

        $statement = new SimpleStatement(
            "CREATE INDEX ON {$this->tableNamePrefix}_with_index (value)"
        );
        $this->session->execute($statement);

        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertNotNull($keyspace);

        $table = $keyspace->table("{$this->tableNamePrefix}_with_index");
        $this->assertNotNull($table);

        $indexOptions = $table->column("value")->indexOptions();
        $this->assertNotNull($indexOptions);
        $this->assertInstanceOf('Cassandra\Map', $indexOptions);
    }

    /**
     * Schema metadata data with null fields.
     *
     * This test ensures that table and column metadata with null fields
     * are returned correctly.
     *
     * @test
     */
    public function testSchemaMetadataWithNullFields() {
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_null_comment (key int PRIMARY KEY, value int)"
        );
        $this->session->execute($statement);

        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $table = $keyspace->table("{$this->tableNamePrefix}_null_comment");
        $this->assertNull($table->comment());

        $column = $table->column("value");
        $this->assertNull($column->indexName());
    }

    /**
     * Schema metadata data with deeply nested collection.
     *
     * This test ensures that the validator parser correctly parses and builds
     * columns with deeply nested collection types.
     *
     * @todo: Add "varchar" and "text" tests
     *
     * @test
     * @ticket PHP-62
     */
    public function testSchemaMetadataWithNestedColumnTypes() {
        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_nested1 (key int PRIMARY KEY, value map<frozen<list<int>>, int>)"
        );
        $this->session->execute($statement);

        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_nested2 (key int PRIMARY KEY, value map<int, frozen<list<int>>>)"
        );
        $this->session->execute($statement);

        $statement = new SimpleStatement(
            "CREATE TABLE {$this->tableNamePrefix}_nested3 (key int PRIMARY KEY, value list<frozen<map<int, frozen<set<int>>>>>)"
        );
        $this->session->execute($statement);

        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);

        $table1 = $keyspace->table("{$this->tableNamePrefix}_nested1");
        $this->assertEquals((string)$table1->column("value")->type(), "map<list<int>, int>");

        $table2 = $keyspace->table("{$this->tableNamePrefix}_nested2");
        $this->assertEquals((string)$table2->column("value")->type(), "map<int, list<int>>");

        $table3 = $keyspace->table("{$this->tableNamePrefix}_nested3");
        $this->assertEquals((string)$table3->column("value")->type(), "list<map<int, set<int>>>");
    }

    /**
     * Schema metadata to validate no secondary indexes exist
     *
     * This test ensures that secondary indexes are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-80
     * @cassandra-version-1.2
     */
    public function testNoSecondaryIndexes() {
        // Create the table and validate no indexes exist
        $this->createTableForSecondaryIndexes();
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table($this->tableNamePrefix);
        $this->assertCount(0, $table->indexes());
        $this->assertFalse($table->index("invalid"));
    }

    /**
     * Schema metadata to validate secondary indexes exist
     *
     * This test ensures that secondary indexes are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-80
     * @cassandra-version-1.2
     */
    public function testSecondaryIndexes() {
        // Create the table and secondary index
        $this->createTableForSecondaryIndexes();
        $this->createSimpleSecondaryIndex();

        // Validate the schema metadata (no indexes exist)
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table($this->tableNamePrefix);
        $this->assertCount(1, $table->indexes());
        $index = $table->index("simple");
        $this->assertIndex($index, "composites", "value1", false, false);
    }

    /**
     * Schema metadata to validate collection secondary indexes exist
     *
     * This test ensures that secondary indexes are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-80
     * @cassandra-version-2.1
     */
    public function testCollectionSecondaryIndexes() {
        // Create the table and secondary index
        $this->createTableForSecondaryIndexes();
        $this->createCollectionSecondaryIndex();

        // Validate the schema metadata
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table($this->tableNamePrefix);
        $this->assertCount(1, $table->indexes());
        $index = $table->index("collection");
        $this->assertIndex($index, "composites", "keys(value2)", false, false);
    }

    /**
     * Schema metadata to validate secondary indexes exist using iterator
     *
     * This test ensures that secondary indexes are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-80
     * @cassandra-version-2.1
     */
    public function testIteratorSecondaryIndexes() {
        // Create the table and secondary indexes
        $this->createTableForSecondaryIndexes();
        $this->createSimpleSecondaryIndex();
        $this->createCollectionSecondaryIndex();

        // Validate the schema metadata
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table($this->tableNamePrefix);
        $indexes = $table->indexes();
        $this->assertCount(2, $indexes);
        foreach ($indexes as $index) {
            $this->assertTrue($index->name() == "simple" || $index->name() == "collection");
        }
    }

    /**
     * Schema metadata to validate no materialized views exist
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-3.0
     */
    public function testNoMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Validate the schema metadata (no materialized views exist)
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->materializedViews());
        $this->assertFalse($keyspace->materializedView("invalid"));
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table("{$this->tableNamePrefix}_1");
        $this->assertCount(0, $table->materializedViews());
        $this->assertFalse($table->materializedView("invalid"));
        $table = $this->session->schema()->keyspace($this->keyspaceName)->table("{$this->tableNamePrefix}_2");
        $this->assertCount(0, $table->materializedViews());
        $this->assertFalse($table->materializedView("invalid"));
    }

    /**
     * Schema metadata to validate a simple materialized view exist
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-3.0
     */
    public function testMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Create the materialized view
        $this->createSimpleMaterializedView();

        // Validate the schema metadata (a single materialized views exist)
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->materializedViews());
        $table = $keyspace->table("{$this->tableNamePrefix}_1");
        $this->assertCount(1, $table->materializedViews());
        $materializedView = $keyspace->materializedView("simple");
        $this->assertMaterializedViewsEqual($materializedView, $table->materializedView("simple"));
        $this->assertMaterializedView($materializedView, "simple", "{$this->tableNamePrefix}_1",
            array("value1", "key1"), array("value1", "key1"), array("key1"));
    }

    /**
     * Schema metadata to validate a materialized view exist when using a
     * primary key
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-3.0
     */
    public function testPrimaryKeyMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Create the materialized view
        $this->createPrimaryKeyMaterializedView();

        // Validate the schema metadata (two materialized views should exist now)
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->materializedViews());
        $table = $keyspace->table("{$this->tableNamePrefix}_2");
        $this->assertCount(1, $table->materializedViews());
        $materializedView = $keyspace->materializedView("primary_key");
        $this->assertMaterializedViewsEqual($materializedView, $table->materializedView("primary_key"));
        $this->assertMaterializedView($materializedView, "primary_key", "{$this->tableNamePrefix}_2",
            array("value1", "key2", "key1"), array("value1", "key2"), array("key1"));
    }

    /**
     * Schema metadata to validate a materialized view exist when using a
     * clustering key
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-3.0
     */
    public function testClusteringKeyMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Create the materialized view
        $this->createClusteringKeyMaterializedView();

        // Validate the schema metadata (three materialized views should exist now as well as two in table 2)
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->materializedViews());
        $table = $keyspace->table("{$this->tableNamePrefix}_2");
        $this->assertCount(1, $table->materializedViews());
        $materializedView = $keyspace->materializedView("clustering_key");
        $this->assertMaterializedViewsEqual($materializedView, $table->materializedView("clustering_key"));
        $this->assertMaterializedView($materializedView, "clustering_key", "{$this->tableNamePrefix}_2",
            array("value1", "key2", "key1"), array("value1"), array("key2", "key1"));
    }

    /**
     * Schema metadata to validate materialized views exist using iterator
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-version-3.0
     */
    public function testIteratorMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Create the materialized views
        $this->createSimpleMaterializedView();
        $this->createPrimaryKeyMaterializedView();
        $this->createClusteringKeyMaterializedView();

        // Validate the materialized views
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(3, $keyspace->materializedViews());
        $table = $keyspace->table("{$this->tableNamePrefix}_1");
        $materializedViews = $table->materializedViews();
        foreach ($materializedViews as $materializedView) {
            if ($materializedView->name() == "simple") {
                $this->assertMaterializedView($materializedView, "simple",
                    "{$this->tableNamePrefix}_1", array("value1", "key1"),
                    array("value1"), array("key1"));
            } else {
                $this->fail("Invalid Materialized View Name: {$materializedView->name()}");
            }
        }
        $this->assertCount(1, $materializedViews);
        $table = $keyspace->table("{$this->tableNamePrefix}_2");
        $materializedViews = $table->materializedViews();
        $this->assertCount(2, $materializedViews);
        foreach ($materializedViews as $materializedView) {
            if ($materializedView->name() == "primary_key") {
                $this->assertMaterializedView($materializedView, "primary_key",
                    "{$this->tableNamePrefix}_2", array("value1", "key2", "key1"),
                    array("value1", "key2"), array("key1"));
            } else if ($materializedView->name() == "clustering_key") {
                $this->assertMaterializedView($materializedView, "clustering_key",
                    "{$this->tableNamePrefix}_2", array("value1", "key2", "key1"),
                    array("value1"), array("key2", "key1"));
            } else {
                $this->fail("Invalid Materialized View Name: {$materializedView->name()}");
            }
        }
    }

    /**
     * Schema metadata to validate materialized views are dropped correctly
     *
     * This test ensures that materialized views are properly handled by the
     * driver.
     *
     * @test
     * @ticket PHP-79
     * @cassandra-version-3.0
     */
    public function testDropMaterializedViews() {
        // Create the tables
        $this->createTablesForMaterializedViews();

        // Create the materialized views
        $this->createSimpleMaterializedView();

        // Validate the materialized view exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->materializedViews());
        $this->assertNotEmpty($keyspace->materializedView("simple"));

        // Drop the materialized view and validate it no longer exists
        $statement = new SimpleStatement("DROP MATERIALIZED VIEW simple");
        $this->session->execute($statement);
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->materializedViews());
        $this->assertEmpty($keyspace->materializedView("simple"));
    }

    /**
     * Schema metadata to validate no UDFs exist
     *
     * This test ensures that UDFs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testNoUserDefinedFunctions() {
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->functions());
        $this->assertFalse($keyspace->function("invalid"));
    }

    /**
     * Schema metadata to validate a UDF exist
     *
     * This test ensures that UDFs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-2.2
     */
    public function testUserDefinedFunctions() {
        // Create the UDF
        $this->createUserDefinedFunction();

        // Validate the UDF exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->functions());
        $this->assertUserDefinedFunction();
    }

    /**
     * Schema metadata to validate UDFs exist using iterator
     *
     * This test ensures that UDFs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testIteratorUserDefinedFunctions() {
        // Create the UDFs (createUserDefinedAggregate creates both functions)
        $this->createUserDefinedAggregate();

        // Validate the UDFs exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(2, $keyspace->functions());
        foreach ($keyspace->functions() as $function) {
            if ($function->simpleName() == "user_defined_function") {
                $this->assertUserDefinedFunction();
            } else if ($function->simpleName() == "uda_udf_final") {
                $this->assertAggregateUserDefinedFunction();
            } else {
                $this->fail("Invalid Function Name: {$function->simpleName()}");
            }
        }
    }

    /**
     * Schema metadata to validate UDFs are dropped correctly
     *
     * This test ensures that UDFs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testDropUserDefinedFunctions() {
        // Create the UDF
        $this->createUserDefinedFunction();

        // Validate the UDF exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->functions());
        $this->assertUserDefinedFunction();

        // Drop the UDF and validate it no longer exists
        $statement = new SimpleStatement("DROP FUNCTION user_defined_function");
        $this->session->execute($statement);
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->functions());
        $this->assertEmpty($keyspace->function("user_defined_function"));
    }

    /**
     * Schema metadata to validate no UDAs exist
     *
     * This test ensures that UDAs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testNoUserDefinedAggregates() {
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->aggregates());
        $this->assertFalse($keyspace->aggregate("invalid"));
    }

    /**
     * Schema metadata to validate a UDA exist
     *
     * This test ensures that UDAs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-2.2
     */
    public function testUserDefinedAggregates() {
        // Create the UDA
        $this->createUserDefinedAggregate();

        // Validate the UDA exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->aggregates());
        $this->assertCount(2, $keyspace->functions());
        $this->assertUserDefinedAggregate();
    }

    /**
     * Schema metadata to validate UDAs exist using iterator
     *
     * This test ensures that UDAs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testIteratorUserDefinedAggregates() {
        // Create the UDAs (the same UDA with a different name)
        $this->createUserDefinedAggregate();
        $statement = new SimpleStatement(
            "CREATE OR REPLACE AGGREGATE user_defined_aggregate_repeat(int) " .
            "SFUNC user_defined_function " .
            "STYPE int " .
            "FINALFUNC uda_udf_final " .
            "INITCOND 0"
        );
        $this->session->execute($statement);

        // Validate the UDAs exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(2, $keyspace->aggregates());
        $count = 0;
        foreach ($keyspace->aggregates() as $aggregate) {
            if ($aggregate->simpleName() == "user_defined_aggregate") {
                $this->assertUserDefinedFunction();
                $count++;
            } else if ($aggregate->simpleName() == "user_defined_aggregate_repeat") {
                $count++;
            } else {
                $this->fail("Invalid Aggregate Name: {$aggregate->simpleName()}");
            }
        }
        $this->assertEquals(2, $count);
    }

    /**
     * Schema metadata to validate UDAs are dropped correctly
     *
     * This test ensures that UDAs are properly handled by the driver.
     *
     * @test
     * @ticket PHP-66
     * @cassandra-version-2.2
     */
    public function testDropUserDefinedAggregates() {
        // Create the UDA
        $this->createUserDefinedAggregate();

        // Validate the UDA exists
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(1, $keyspace->aggregates());
        $this->assertCount(2, $keyspace->functions());
        $this->assertUserDefinedAggregate();

        // Drop the UDA and validate it no longer exists
        $statement = new SimpleStatement("DROP AGGREGATE user_defined_aggregate");
        $this->session->execute($statement);
        $keyspace = $this->session->schema()->keyspace($this->keyspaceName);
        $this->assertCount(0, $keyspace->aggregates());
        $this->assertEmpty($keyspace->function("user_defined_aggregate"));
        $this->assertCount(2, $keyspace->functions());
    }

    /**
     * Schema metadata versioning
     *
     * This test ensures that schema metadata has a version identifier to
     * quickly determine if one schema is different than another.
     *
     * @test
     */
    public function testVersion() {
        // Ensure the version information is available
        $version = $this->session->schema()->version();
        $this->assertGreaterThan(0, $version);

        // Ensure the version is incremented by forcing a keyspace created event
        $this->createKeyspace("{$this->keyspaceName}_new");
        $this->assertEquals($version + 1, $this->session->schema()->version());

        // Ensure the version is not incremented (no changes occurred)
        $this->assertEquals($version + 1, $this->session->schema()->version());
    }
}
