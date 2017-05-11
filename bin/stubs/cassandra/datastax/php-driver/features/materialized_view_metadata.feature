@cassandra-version-3.0
Feature: Materialized View Metadata

  PHP Driver exposes the Cassandra Schema Metadata for materialized views.

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
      CREATE MATERIALIZED VIEW IF NOT EXISTS users_view AS SELECT name FROM users WHERE name IS NOT NULL PRIMARY KEY(name, id);
      """

  Scenario: Getting a materialized view
    Given the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                        ->withContactPoints('127.0.0.1')
                        ->build();
      $session = $cluster->connect("simplex");
      $schema = $session->schema();
      $view = $schema->keyspace("simplex")->materializedView("users_view");

      echo "Name: " . $view->name() . "\n";
      echo "BaseTable: " . $view->baseTable()->name() . "\n";
      echo "DefaultTimeToLive: " . $view->option("default_time_to_live") . "\n";
      echo "Compression: " . var_export($view->option("compression"), true) . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Name: users_view
      BaseTable: users
      DefaultTimeToLive: 0
      Compression: Cassandra\Map::__set_state(array(
         'type' =>
        Cassandra\Type\Map::__set_state(array(
           'keyType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'varchar',
          )),
           'valueType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'varchar',
          )),
        )),
         'keys' =>
        array (
          0 => 'chunk_length_in_kb',
          1 => 'class',
        ),
         'values' =>
        array (
          0 => '64',
          1 => 'org.apache.cassandra.io.compress.LZ4Compressor',
        ),
      ))
      """

