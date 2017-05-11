Feature: Secondary Index Metadata

  PHP Driver exposes the Cassandra Schema Metadata for secondary indexes.

  Background:
    Given a running Cassandra cluster
    And the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      } AND DURABLE_WRITES = false;
      USE simplex;
      CREATE TABLE users (id int PRIMARY KEY, name text);
      CREATE INDEX IF NOT EXISTS name_index ON users(name);
      """

  Scenario: Getting a index metadata
    Given the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                        ->withContactPoints('127.0.0.1')
                        ->build();
      $session = $cluster->connect("simplex");
      $schema = $session->schema();
      $index = $schema->keyspace("simplex")->table("users")->index("name_index");

      echo "Name: " . $index->name() . "\n";
      echo "Kind: " . $index->kind() . "\n";
      echo "Target: " . $index->target() . "\n";
      echo "IsCustom: " . ($index->isCustom() ? "true" : "false") . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: name_index
      Kind: composites
      Target: name
      IsCustom: false
      """

  @cassandra-version-3.0
  Scenario: Getting a index metadata w/ options
    Given the following example:
      """php
      <?php
      $cluster  = Cassandra::cluster()
                         ->withContactPoints('127.0.0.1')
                         ->build();
      $session  = $cluster->connect("simplex");

      $schema   = $session->schema();

      $index = $schema->keyspace("simplex")->table("users")->index("name_index");

      echo "Name: " . $index->name() . "\n";
      echo "Kind: " . $index->kind() . "\n";
      echo "Target: " . $index->target() . "\n";
      echo "IsCustom: " . ($index->isCustom() ? "true" : "false") . "\n";
      echo "Options: " . var_export($index->options(), true) . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: name_index
      Kind: composites
      Target: name
      IsCustom: false
      Options: array (
        'target' => 'name',
      )
      """

  @cassandra-version-3.0
  Scenario: Getting a custom index metadata
    Given additional schema:
      """
      CREATE CUSTOM INDEX name_custom_index ON users (name) USING 'org.apache.cassandra.index.internal.composites.ClusteringColumnIndex';
      """
    And the following example:
      """php
      <?php
      $cluster  = Cassandra::cluster()
                         ->withContactPoints('127.0.0.1')
                         ->build();
      $session  = $cluster->connect("simplex");

      $schema   = $session->schema();

      $index = $schema->keyspace("simplex")->table("users")->index("name_custom_index");

      echo "Name: " . $index->name() . "\n";
      echo "Kind: " . $index->kind() . "\n";
      echo "Target: " . $index->target() . "\n";
      echo "IsCustom: " . ($index->isCustom() ? "true" : "false") . "\n";
      echo "ClassName: " . $index->className() . "\n";
      echo "Options: " . var_export($index->options(), true) . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: name_custom_index
      Kind: custom
      Target: name
      IsCustom: true
      ClassName: org.apache.cassandra.index.internal.composites.ClusteringColumnIndex
      Options: array (
        'class_name' => 'org.apache.cassandra.index.internal.composites.ClusteringColumnIndex',
        'target' => 'name',
      )
      """
