Feature: Datatypes

  PHP Driver supports all Cassandra datatypes

  Background:
    Given a running Cassandra cluster

  Scenario: Using Cassandra value types
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE values (
        id int PRIMARY KEY,
        bigint_value bigint,
        decimal_value decimal,
        double_value double,
        float_value float,
        int_value int,
        varint_value varint,
        timestamp_value timestamp,
        blob_value blob,
        uuid_value uuid,
        timeuuid_value timeuuid,
        inet_value inet
      );
      INSERT INTO values (
        id,
        bigint_value,
        decimal_value,
        double_value,
        float_value,
        int_value,
        varint_value,
        timestamp_value,
        blob_value,
        uuid_value,
        timeuuid_value,
        inet_value
      )
      VALUES (
        0,
        -765438000,
        1313123123.234234234234234234123,
        3.141592653589793,
        3.14,
        4,
        67890656781923123918798273492834712837198237,
        1425691864001,
        varcharAsBlob('0x000000'),
        ab3352d9-4f7f-4007-a35a-e62aa7ab0b19,
        maxTimeuuid('2015-03-11 14:47:10+0000'),
        '200.199.198.197'
      )
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM values");
      $result    = $session->execute($statement);
      $row       = $result->first();

      echo "Bigint: " . var_export($row['bigint_value'], true) . "\n";
      echo "Decimal: " . var_export($row['decimal_value'], true) . "\n";
      echo "Double: " . sprintf('%.13f', $row['double_value']) . "\n";
      echo "Float: " . var_export($row['float_value'], true) . "\n";
      echo "Int: " . var_export($row['int_value'], true) . "\n";
      echo "Varint: " . var_export($row['varint_value'], true) . "\n";
      echo "Timestamp: " . var_export($row['timestamp_value'], true) . "\n";
      echo "Blob: " . var_export($row['blob_value'], true) . "\n";
      echo "Uuid: " . var_export($row['uuid_value'], true) . "\n";
      echo "Timeuuid: " . var_export($row['timeuuid_value'], true) . "\n";
      echo "Inet: " . var_export($row['inet_value'], true) . "\n";
      """
    When it is executed
    Then its output should contain:
      """
Bigint: Cassandra\Bigint::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'bigint',
        )),
         'value' => '-765438000',
      ))
      Decimal: Cassandra\Decimal::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'decimal',
        )),
         'value' => '1313123123234234234234234234123',
         'scale' => 21,
      ))
      Double: 3.1415926535898
      Float: Cassandra\Float::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'float',
        )),
         'value' => '3.14000010490417',
      ))
      Int: 4
      Varint: Cassandra\Varint::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'varint',
        )),
         'value' => '67890656781923123918798273492834712837198237',
      ))
      Timestamp: Cassandra\Timestamp::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'timestamp',
        )),
         'seconds' => 1425691864,
         'microseconds' => 1000,
      ))
      Blob: Cassandra\Blob::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'blob',
        )),
         'bytes' => '0x3078303030303030',
      ))
      Uuid: Cassandra\Uuid::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'uuid',
        )),
         'uuid' => 'ab3352d9-4f7f-4007-a35a-e62aa7ab0b19',
         'version' => 4,
      ))
      Timeuuid: Cassandra\Timeuuid::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'timeuuid',
        )),
         'uuid' => '7f0a920f-c7fd-11e4-7f7f-7f7f7f7f7f7f',
         'version' => 1,
      ))
      Inet: Cassandra\Inet::__set_state(array(
         'type' =>
        Cassandra\Type\Scalar::__set_state(array(
           'name' => 'inet',
        )),
         'address' => '200.199.198.197',
      ))
      """

  @cassandra-version-2.2
  @tinyint
  @smallint
  Scenario: Using Cassandra tinyint and smallint types
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE values (
        id int PRIMARY KEY,
        tinyint_value tinyint,
        smallint_value smallint,
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");

      $statement = new Cassandra\SimpleStatement("INSERT INTO values (id, tinyint_value, smallint_value) VALUES (?, ?, ?)");
      $options   = new Cassandra\ExecutionOptions(array('arguments' =>
        array(1, new Cassandra\Tinyint(127), new Cassandra\Smallint(32767))
      ));
      $session->execute($statement, $options);

      $statement = new Cassandra\SimpleStatement("SELECT * FROM values");
      $result    = $session->execute($statement);
      $row       = $result->first();

      echo "Tinyint: " . $row['tinyint_value']->value() . "\n";
      echo "Smallint: " . $row['smallint_value']->value() . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Tinyint: 127
      Smallint: 32767
      """

  @cassandra-version-2.2
  @date
  Scenario: Using Cassandra date type
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE date_values (
        id int PRIMARY KEY,
        date_value date
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");

      $statement = new Cassandra\SimpleStatement("INSERT INTO date_values (id, date_value) VALUES (?, ?)");
      $options   = new Cassandra\ExecutionOptions(array('arguments' => array(1, new Cassandra\Date(0))));
      $session->execute($statement, $options);

      $statement = new Cassandra\SimpleStatement("SELECT * FROM date_values");
      $result    = $session->execute($statement);
      $row       = $result->first();

      echo "Date: " . $row['date_value']->toDateTime()->format("Y-m-d H:i:s") . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Date: 1970-01-01 00:00:00
      """

  @cassandra-version-2.2
  @time
  Scenario: Using Cassandra time type
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE time_values (
        id int PRIMARY KEY,
        time_value time
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");

      $statement = new Cassandra\SimpleStatement("INSERT INTO time_values (id, time_value) VALUES (?, ?)");
      $datetime = new \DateTime("1970-01-01T00:00:01+0000");
      $options   = new Cassandra\ExecutionOptions(array('arguments' => array(1, Cassandra\Time::fromDateTime($datetime))));
      $session->execute($statement, $options);

      $statement = new Cassandra\SimpleStatement("SELECT * FROM time_values");
      $result    = $session->execute($statement);
      $row       = $result->first();

      echo "Time: " . $row['time_value'] . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Time: 1000000000
      """
