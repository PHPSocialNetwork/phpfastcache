@cassandra-version-2.0
Feature: Batch statements

  PHP Driver supports batch statements. There are three types of batch statements:

    * `Cassandra::BATCH_LOGGED`   - this is the default batch type. This batch
      guarantees that either all or none of its statements will be executed.
      This behavior is achieved by writing a batch log on the coordinator, which
      slows down the execution somewhat.
    * `Cassandra::BATCH_UNLOGGED` - this batch will not be verified when executed,
      which makes it faster than a `LOGGED` batch, but means that some of its statements
      might fail, while others - succeed.
    * `Cassandra::BATCH_COUNTER`  - this batch is used for counter updates, which
      are, unlike other writes, not idempotent.

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

  Scenario: Batch statements can contain simple and prepared statements
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $prepared  = $session->prepare(
                     "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
                   );
      $simple    = new Cassandra\SimpleStatement(
                     "INSERT INTO playlists (id, song_id, artist, title, album) " .
                     "VALUES (62c36092-82a1-3a00-93d1-46196ee77204, ?, ?, ?, ?)"
                   );
      $batch     = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);

      $batch->add($prepared, array(
          'song_id' => new Cassandra\Uuid('756716f7-2e54-4715-9f00-91dcbea6cf50'),
          'title'   => 'La Petite Tonkinoise',
          'album'   => 'Bye Bye Blackbird',
          'artist'  => 'Joséphine Baker'
      ));

      $batch->add($simple, array(
          new Cassandra\Uuid('f6071e72-48ec-4fcb-bf3e-379c8a696488'),
          'Willi Ostermann', 'Die Mösch', 'In Gold',
      ));

      $batch->add($prepared, array(
          new Cassandra\Uuid('fbdf82ed-0063-4796-9c7c-a3d4f47b4b25'),
          'Mick Jager', 'Memo From Turner', 'Performance'
      ));

      $session->execute($batch);

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
