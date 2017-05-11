Feature: Simple Statements

  PHP Driver supports simple statements.

  Background:
    Given a running Cassandra cluster
    And the following schema:
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
      """

  Scenario: Simple statements are initialized with a CQL string
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM playlists");
      $result    = $session->execute($statement);
      echo "Result contains " . $result->count() . " rows";
      """
    When it is executed
    Then its output should contain:
      """
      Result contains 0 rows
      """

  @cassandra-version-2.0
  Scenario: Simple statements support positional arguments
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement(
                     "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
                   );

      $songs = array(
          array(
              new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50'),
              'Joséphine Baker',
              'La Petite Tonkinoise',
              'Bye Bye Blackbird'
          ),
          array(
              new Cassandra\Uuid('f6071e72-48ec-4fcb-bf3e-379c8a696488'),
              'Willi Ostermann',
              'Die Mösch',
              'In Gold'
          ),
          array(
              new Cassandra\Uuid('fbdf82ed-0063-4796-9c7c-a3d4f47b4b25'),
              'Mick Jager',
              'Memo From Turner',
              'Performance'
          ),
      );

      foreach ($songs as $song) {
          $options = new Cassandra\ExecutionOptions(array('arguments' => $song));
          $session->execute($statement, $options);
      }

      $statement = new Cassandra\SimpleStatement("SELECT * FROM simplex.playlists");
      $result    = $session->execute($statement);

      foreach ($result as $row) {
        echo $row['artist'] . ": " . $row['title'] . " / " . $row['album'] . "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      Joséphine Baker: La Petite Tonkinoise / Bye Bye Blackbird
      """
    And its output should contain:
      """
      Willi Ostermann: Die Mösch / In Gold
      """
    And its output should contain:
      """
      Mick Jager: Memo From Turner / Performance
      """

  @cassandra-version-2.1
  Scenario: Simple statements also support named arguments
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement(
                     "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
                   );

      $songs = array(
          array(
              'song_id' => new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50'),
              'artist'  => 'Joséphine Baker',
              'title'   => 'La Petite Tonkinoise',
              'album'   => 'Bye Bye Blackbird'
          ),
          array(
              'song_id' => new Cassandra\Uuid('f6071e72-48ec-4fcb-bf3e-379c8a696488'),
              'artist'  => 'Willi Ostermann',
              'title'   => 'Die Mösch',
              'album'   => 'In Gold'
          ),
          array(
              'song_id' => new Cassandra\Uuid('fbdf82ed-0063-4796-9c7c-a3d4f47b4b25'),
              'artist'  => 'Mick Jager',
              'title'   => 'Memo From Turner',
              'album'   => 'Performance'
          ),
      );

      foreach ($songs as $song) {
          $options = new Cassandra\ExecutionOptions(array('arguments' => $song));
          $session->execute($statement, $options);
      }

      $statement = new Cassandra\SimpleStatement("SELECT * FROM simplex.playlists");
      $result    = $session->execute($statement);

      foreach ($result as $row) {
        echo $row['artist'] . ": " . $row['title'] . " / " . $row['album'] . "\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      Joséphine Baker: La Petite Tonkinoise / Bye Bye Blackbird
      """
    And its output should contain:
      """
      Willi Ostermann: Die Mösch / In Gold
      """
    And its output should contain:
      """
      Mick Jager: Memo From Turner / Performance
      """

  @cassandra-version-2.1
  Scenario: Simple statements also supports ":name" arguments
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement(
          "INSERT INTO playlists (id, song_id, artist, title, album) " .
          "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
      );

      $songs = array(
          array(
              'song_id' => new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50'),
              'artist'  => 'Joséphine Baker',
              'title'   => 'La Petite Tonkinoise',
              'album'   => 'Bye Bye Blackbird'
          ),
      );

      foreach ($songs as $song) {
          $options = new Cassandra\ExecutionOptions(array('arguments' => $song));
          $session->execute($statement, $options);
      }

      $statement = new Cassandra\SimpleStatement(
          "SELECT * FROM simplex.playlists " .
          "WHERE id = :id AND artist = :artist AND title = :title AND album = :album"
      );

      $options = new Cassandra\ExecutionOptions(
          array('arguments' =>
              array(
                  'id'     => new Cassandra\Uuid('62c36092-82a1-3a00-93d1-46196ee77204'),
                  'artist' => 'Joséphine Baker',
                  'title'  => 'La Petite Tonkinoise',
                  'album'  => 'Bye Bye Blackbird'
              )
          )
      );

      $result = $session->execute($statement, $options);

      $row = $result->first();
      echo $row['artist'] . ": " . $row['title'] . " / " . $row['album'] . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      Joséphine Baker: La Petite Tonkinoise / Bye Bye Blackbird
      """
