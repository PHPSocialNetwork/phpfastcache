Feature: Collections

  PHP Driver supports all Cassandra collections

  Background:
    Given a running Cassandra cluster

  Scenario: Using Cassandra collections
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE user (
        id int PRIMARY KEY,
        logins list<timestamp>,
        locations map<timestamp, double>,
        ip_addresses set<inet>
      );
      INSERT INTO user (id, logins, locations, ip_addresses)
      VALUES (
        0,
        ['2014-09-11 10:09:08+0000', '2014-09-12 10:09:00+0000'],
        {'2014-09-11 10:09:08+0000': 37.397357},
        {'200.199.198.197', '192.168.1.15'}
      )
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM user");
      $result    = $session->execute($statement);
      $row       = $result->first();

      echo "Logins: " . var_export($row['logins'], true) . "\n";
      echo "Locations: " . var_export($row['locations'], true) . "\n";
      echo "Ip Addresses: " . var_export($row['ip_addresses'], true) . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Logins: Cassandra\Collection::__set_state(array(
         'type' =>
        Cassandra\Type\Collection::__set_state(array(
           'valueType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'timestamp',
          )),
        )),
         'values' =>
        array (
          0 =>
          Cassandra\Timestamp::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'timestamp',
            )),
             'seconds' => 1410430148,
             'microseconds' => 0,
          )),
          1 =>
          Cassandra\Timestamp::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'timestamp',
            )),
             'seconds' => 1410516540,
             'microseconds' => 0,
          )),
        ),
      ))
      Locations: Cassandra\Map::__set_state(array(
         'type' =>
        Cassandra\Type\Map::__set_state(array(
           'keyType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'timestamp',
          )),
           'valueType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'double',
          )),
        )),
         'keys' =>
        array (
          0 =>
          Cassandra\Timestamp::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'timestamp',
            )),
             'seconds' => 1410430148,
             'microseconds' => 0,
          )),
        ),
         'values' =>
        array (
          0 => 37.397357,
        ),
      ))
      Ip Addresses: Cassandra\Set::__set_state(array(
         'type' =>
        Cassandra\Type\Set::__set_state(array(
           'valueType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'inet',
          )),
        )),
         'values' =>
        array (
          0 =>
          Cassandra\Inet::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'inet',
            )),
             'address' => '192.168.1.15',
          )),
          1 =>
          Cassandra\Inet::__set_state(array(
             'type' =>
            Cassandra\Type\Scalar::__set_state(array(
               'name' => 'inet',
            )),
             'address' => '200.199.198.197',
          )),
        ),
      ))
      """

  @cassandra-version-2.1
  Scenario: Using Cassandra nested collections
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
        addresses map<text, frozen<map<text, text>>>
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

      $addressType = Cassandra\Type::map(Cassandra\Type::text(), Cassandra\Type::text());
      $addressesType = Cassandra\Type::map(Cassandra\Type::text(), $addressType);

      $users = array(
          array(
              new Cassandra\Uuid('56357d2b-4586-433c-ad24-afa9918bc415'),
              'Charles Wallace',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'Phoenix',
                      'street', '9042 Cassandra Lane',
                      'zip', '85023'))
          ),
          array(
              new Cassandra\Uuid('ce359590-8528-4682-a9f3-add53fc9aa09'),
              'Kevin Malone',
              $addressesType->create(
                  'home', $addressType->create(
                      'city', 'New York',
                      'street', '1000 Database Road',
                      'zip', '10025')
              )
          ),
          array(
              new Cassandra\Uuid('7d64dca1-dd4d-4f3c-bec4-6a88fa082a13'),
              'Michael Scott',
              $addressesType->create(
                  'work', $addressType->create(
                      'city', 'Santa Clara',
                      'street', '20000 Log Ave',
                      'zip', '95054'))
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
          print 'Addresses: ' . var_export($row['addresses'], true). "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      ID: 56357d2b-4586-433c-ad24-afa9918bc415
      Name: Charles Wallace
      Addresses: Cassandra\Map::__set_state(array(
         'type' =>
        Cassandra\Type\Map::__set_state(array(
           'keyType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'varchar',
          )),
           'valueType' =>
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
        )),
         'keys' =>
        array (
          0 => 'home',
        ),
         'values' =>
        array (
          0 =>
          Cassandra\Map::__set_state(array(
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
              0 => 'city',
              1 => 'street',
              2 => 'zip',
            ),
             'values' =>
            array (
              0 => '85023',
              1 => '9042 Cassandra Lane',
              2 => 'Phoenix',
            ),
          )),
        ),
      ))
      ID: ce359590-8528-4682-a9f3-add53fc9aa09
      Name: Kevin Malone
      Addresses: Cassandra\Map::__set_state(array(
         'type' =>
        Cassandra\Type\Map::__set_state(array(
           'keyType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'varchar',
          )),
           'valueType' =>
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
        )),
         'keys' =>
        array (
          0 => 'home',
        ),
         'values' =>
        array (
          0 =>
          Cassandra\Map::__set_state(array(
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
              0 => 'city',
              1 => 'street',
              2 => 'zip',
            ),
             'values' =>
            array (
              0 => '1000 Database Road',
              1 => '10025',
              2 => 'New York',
            ),
          )),
        ),
      ))
      ID: 7d64dca1-dd4d-4f3c-bec4-6a88fa082a13
      Name: Michael Scott
      Addresses: Cassandra\Map::__set_state(array(
         'type' =>
        Cassandra\Type\Map::__set_state(array(
           'keyType' =>
          Cassandra\Type\Scalar::__set_state(array(
             'name' => 'varchar',
          )),
           'valueType' =>
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
        )),
         'keys' =>
        array (
          0 => 'work',
        ),
         'values' =>
        array (
          0 =>
          Cassandra\Map::__set_state(array(
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
              0 => 'city',
              1 => 'street',
              2 => 'zip',
            ),
             'values' =>
            array (
              0 => '20000 Log Ave',
              1 => '95054',
              2 => 'Santa Clara',
            ),
          )),
        ),
      ))
      """
