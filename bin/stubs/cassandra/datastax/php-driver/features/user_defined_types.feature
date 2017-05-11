@cassandra-version-2.1
Feature: User defined types

  PHP Driver supports Cassandra UDTs

  Background:
    Given a running Cassandra cluster

  Scenario: Using Cassandra user defined types from schema metadata
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TYPE address (street text, city text, zip int);
      CREATE TYPE addresses (home frozen<address>, work frozen<address>);
      CREATE TABLE users (
        id uuid PRIMARY KEY,
        name text,
        addresses frozen<addresses>
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $keyspace = $session->schema()->keyspace("simplex");

      $statement = new Cassandra\SimpleStatement(
                      "INSERT INTO users (id, name, addresses) VALUES (?, ?, ?)");

      $addressType = $keyspace->userType("address");
      $addressesType = $keyspace->userType("addresses");

      $users = array(
          array(
              new Cassandra\Uuid('56357d2b-4586-433c-ad24-afa9918bc415'),
              'Charles Wallace',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'Phoenix',
                      'street', '9042 Cassandra Lane',
                      'zip', 85023))
          ),
          array(
              new Cassandra\Uuid('ce359590-8528-4682-a9f3-add53fc9aa09'),
              'Kevin Malone',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'New York',
                      'street', '1000 Database Road',
                      'zip', 10025),
                  'work', $addressType->create(
                      'city', 'New York',
                      'street', '60  SSTable Drive',
                      'zip', 10024)
              )
          ),
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
          print 'Addresses: ' . var_export($row['addresses'], true). "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      ID: 56357d2b-4586-433c-ad24-afa9918bc415
      Name: Charles Wallace
      Addresses: Cassandra\UserTypeValue::__set_state(array(
         'type' =>
        Cassandra\Type\UserType::__set_state(array(
           'types' =>
          array (
            'home' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
            'work' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
          ),
        )),
         'values' =>
        array (
          'home' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '9042 Cassandra Lane',
              'city' => 'Phoenix',
              'zip' => 85023,
            ),
          )),
          'work' => NULL,
        ),
      ))
      ID: ce359590-8528-4682-a9f3-add53fc9aa09
      Name: Kevin Malone
      Addresses: Cassandra\UserTypeValue::__set_state(array(
         'type' =>
        Cassandra\Type\UserType::__set_state(array(
           'types' =>
          array (
            'home' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
            'work' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
          ),
        )),
         'values' =>
        array (
          'home' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '1000 Database Road',
              'city' => 'New York',
              'zip' => 10025,
            ),
          )),
          'work' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '60  SSTable Drive',
              'city' => 'New York',
              'zip' => 10024,
            ),
          )),
        ),
      ))
      """

  Scenario: Using Cassandra manually create user defined types
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TYPE address (street text, city text, zip int);
      CREATE TYPE addresses (home frozen<address>, work frozen<address>);
      CREATE TABLE users (
        id uuid PRIMARY KEY,
        name text,
        addresses frozen<addresses>
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
                      "INSERT INTO users (id, name, addresses) VALUES (?, ?, ?)");

      $addressType = Cassandra\Type::userType(
          'street', Cassandra\Type::text(),
          'city',   Cassandra\Type::text(),
          'zip',    Cassandra\Type::int()
      );

      $addressesType = Cassandra\Type::userType(
          'home', $addressType,
          'work', $addressType
       );

      $users = array(
          array(
              new Cassandra\Uuid('56357d2b-4586-433c-ad24-afa9918bc415'),
              'Charles Wallace',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'Phoenix',
                      'street', '9042 Cassandra Lane',
                      'zip', 85023))
          ),
          array(
              new Cassandra\Uuid('ce359590-8528-4682-a9f3-add53fc9aa09'),
              'Kevin Malone',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'New York',
                      'street', '1000 Database Road',
                      'zip', 10025),
                  'work', $addressType->create(
                      'city', 'New York',
                      'street', '60  SSTable Drive',
                      'zip', 10024)
              )
          ),
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
          print 'Addresses: ' . var_export($row['addresses'], true). "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      ID: 56357d2b-4586-433c-ad24-afa9918bc415
      Name: Charles Wallace
      Addresses: Cassandra\UserTypeValue::__set_state(array(
         'type' =>
        Cassandra\Type\UserType::__set_state(array(
           'types' =>
          array (
            'home' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
            'work' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
          ),
        )),
         'values' =>
        array (
          'home' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '9042 Cassandra Lane',
              'city' => 'Phoenix',
              'zip' => 85023,
            ),
          )),
          'work' => NULL,
        ),
      ))
      ID: ce359590-8528-4682-a9f3-add53fc9aa09
      Name: Kevin Malone
      Addresses: Cassandra\UserTypeValue::__set_state(array(
         'type' =>
        Cassandra\Type\UserType::__set_state(array(
           'types' =>
          array (
            'home' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
            'work' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
          ),
        )),
         'values' =>
        array (
          'home' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '1000 Database Road',
              'city' => 'New York',
              'zip' => 10025,
            ),
          )),
          'work' =>
          Cassandra\UserTypeValue::__set_state(array(
             'type' =>
            Cassandra\Type\UserType::__set_state(array(
               'types' =>
              array (
                'street' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'city' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'varchar',
                )),
                'zip' =>
                Cassandra\Type\Scalar::__set_state(array(
                   'name' => 'int',
                )),
              ),
            )),
             'values' =>
            array (
              'street' => '60  SSTable Drive',
              'city' => 'New York',
              'zip' => 10024,
            ),
          )),
        ),
      ))
      """
