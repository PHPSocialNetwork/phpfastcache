Feature: Session management

  Each PHP Driver session can be explicitly closed to cleanup its resources.

  Background:
    Given a running Cassandra cluster

  Scenario: Session can only be closed once
    Given the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->withPersistentSessions(false)
                     ->build();
      $session = $cluster->connect();
      $session->close();

      try {
          $session->close();
      } catch (Cassandra\Exception $e) {
          echo get_class($e) . ": " . $e->getMessage() . "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      Cassandra\Exception\RuntimeException: Already closing or closed
      """

  Scenario: Session closes after outstanding requests are completed
    Given the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
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
      INSERT INTO playlists (id, song_id, artist, title, album)
      VALUES (
        62c36092-82a1-3a00-93d1-46196ee77204,
        756716f7-2e54-4715-9f00-91dcbea6cf50,
        'Joséphine Baker',
        'La Petite Tonkinoise',
        'Bye Bye Blackbird'
      );
      INSERT INTO playlists (id, song_id, artist, title, album)
      VALUES (
        62c36092-82a1-3a00-93d1-46196ee77204,
        f6071e72-48ec-4fcb-bf3e-379c8a696488,
        'Willi Ostermann',
        'Die Mösch',
        'In Gold'
      );
      INSERT INTO playlists (id, song_id, artist, title, album)
      VALUES (
        62c36092-82a1-3a00-93d1-46196ee77204,
        fbdf82ed-0063-4796-9c7c-a3d4f47b4b25,
        'Mick Jager',
        'Memo From Turner',
        'Performance'
      );
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM simplex.playlists");
      $future    = $session->executeAsync($statement);
      $session->close();

      echo "Result contains " . $future->get()->count() . " rows";
      """
    When it is executed
    Then its output should contain:
      """
      Result contains 3 rows
      """
