@cassandra-version-2.1
Feature: Tuples

  PHP Driver supports Cassandra tuples

  Background:
    Given a running Cassandra cluster

  Scenario: Using Cassandra tuples
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE users (
        id uuid PRIMARY KEY,
        name text,
        address tuple<text, text, int>
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");

      $statement = new Cassandra\SimpleStatement(
                      "INSERT INTO users (id, name, address) VALUES (?, ?, ?)");

      $tupleType = Cassandra\Type::tuple(Cassandra\Type::text(), Cassandra\Type::text(), Cassandra\Type::int());

      $users = array(
          array(
              new Cassandra\Uuid('56357d2b-4586-433c-ad24-afa9918bc415'),
              'Charles Wallace',
              $tupleType->create('Phoenix', '9042 Cassandra Lane', 85023)
          ),
          array(
              new Cassandra\Uuid('ce359590-8528-4682-a9f3-add53fc9aa09'),
              'Kevin Malone',
              $tupleType->create('New York', '1000 Database Road', 10025)
          ),
          array(
              new Cassandra\Uuid('7d64dca1-dd4d-4f3c-bec4-6a88fa082a13'),
              'Michael Scott',
              $tupleType->create('Santa Clara', '20000 Log Ave', 95054)
          )
      );

      foreach ($users as $user) {
        $options = new Cassandra\ExecutionOptions(array('arguments' => $user));
        $session->execute($statement, $options);
      }

      $statement = new Cassandra\SimpleStatement("SELECT * FROM users");
      $result    = $session->execute($statement);

      foreach ($result as $row) {
          print 'ID: ' . $row['id'] . "\n";
          print 'Name: ' . $row['name'] . "\n";
          print 'Address: ' . var_export($row['address'], true). "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      ID: 56357d2b-4586-433c-ad24-afa9918bc415
      Name: Charles Wallace
      Address: Cassandra\Tuple::__set_state(array(
         'type' =>
        Cassandra\Type\Tuple::__set_state(array(
           'types' =>
          array (
            0 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            1 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            2 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'int',
            )),
          ),
        )),
         'values' =>
        array (
          0 => 'Phoenix',
          1 => '9042 Cassandra Lane',
          2 => 85023,
        ),
      ))
      ID: ce359590-8528-4682-a9f3-add53fc9aa09
      Name: Kevin Malone
      Address: Cassandra\Tuple::__set_state(array(
         'type' =>
        Cassandra\Type\Tuple::__set_state(array(
           'types' =>
          array (
            0 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            1 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            2 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'int',
            )),
          ),
        )),
         'values' =>
        array (
          0 => 'New York',
          1 => '1000 Database Road',
          2 => 10025,
        ),
      ))
      ID: 7d64dca1-dd4d-4f3c-bec4-6a88fa082a13
      Name: Michael Scott
      Address: Cassandra\Tuple::__set_state(array(
         'type' =>
        Cassandra\Type\Tuple::__set_state(array(
           'types' =>
          array (
            0 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            1 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'varchar',
            )),
            2 =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'int',
            )),
          ),
        )),
         'values' =>
        array (
          0 => 'Santa Clara',
          1 => '20000 Log Ave',
          2 => 95054,
        ),
      ))
      """
