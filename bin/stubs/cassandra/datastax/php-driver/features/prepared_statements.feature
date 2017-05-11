Feature: Prepared Statements

  PHP Driver supports prepared statements. Prepared statements must be
  initialized using `Cassandra\Session::prepare()`.

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

  Scenario: Prepared statements support named arguments
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $insert    = $session->prepare(
                     "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
                   );

      $songs = array(
          array(
              'song_id' => new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50'),
              'title'   => 'La Petite Tonkinoise',
              'album'   => 'Bye Bye Blackbird',
              'artist'  => 'Joséphine Baker'
          ),
          array(
              'song_id' => new Cassandra\Uuid('f6071e72-48ec-4fcb-bf3e-379c8a696488'),
              'title'   => 'Die Mösch',
              'album'   => 'In Gold',
              'artist'  => 'Willi Ostermann'
          ),
          array(
              'song_id' => new Cassandra\Uuid('fbdf82ed-0063-4796-9c7c-a3d4f47b4b25'),
              'title'   => 'Memo From Turner',
              'album'   => 'Performance',
              'artist'  => 'Mick Jager'
          ),
      );

      foreach ($songs as $song) {
          $options = new Cassandra\ExecutionOptions(array('arguments' => $song));
          $session->execute($insert, $options);
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
