Feature: Consistency Level

  PHP Driver supports consistency levels when executing statements.

  Background:
    Given a running Cassandra cluster with "3" nodes
    And the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 3
      };
      USE simplex;
      CREATE TABLE playlists (
        id uuid,
        title text,
        album text,
        artist text,
        song_id uuid,
        PRIMARY KEY (id, title, album, artist)
      );
      """

  Scenario: Consistency levels are specified via execution options
    Given tracing is enabled
    And the following example:
      """php
      <?php
      $cluster     = Cassandra::cluster()
                       ->withContactPoints('127.0.0.1')
                       ->build();
                       $session     = $cluster->connect("simplex");

      $insertQuery = "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, " .
                         new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50') . ", " .
                         "'Joséphine Baker', " .
                         "'La Petite Tonkinoise', " .
                         "'Bye Bye Blackbird')";
      $statement   = new Cassandra\SimpleStatement($insertQuery);
      $options     = new Cassandra\ExecutionOptions(array('consistency' => Cassandra::CONSISTENCY_ALL));
      $session->execute($statement, $options);

      // Below uses the system_traces.events table to verify consistency ALL is met
      $statement = new Cassandra\SimpleStatement("SELECT source from system_traces.events");
      $result    = $session->execute($statement, $options);
      $sources   = array();
      foreach ($result as $row) {
          array_push($sources, (string) $row['source']);
      }
      $sources = array_unique($sources);
      asort($sources);
      foreach ($sources as $source) {
          echo $source . "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      127.0.0.1
      127.0.0.2
      127.0.0.3
      """
