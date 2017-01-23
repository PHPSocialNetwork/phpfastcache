Feature: Session Object

  The DataStax drivers use session object(s) to perform operations against a
  Cassandra cluster

  Background:
    Given a running Cassandra cluster

  Scenario: Sessions can also operate on different keyspaces
    Given the following schema:
      """cql
      CREATE KEYSPACE french WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      CREATE KEYSPACE german WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      CREATE KEYSPACE uk WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      CREATE TABLE french.playlists (
        id uuid,
        title text,
        album text,
        artist text,
        song_id uuid,
        PRIMARY KEY (id, title, album, artist)
      );
      CREATE TABLE german.playlists (
        id uuid,
        title text,
        album text,
        artist text,
        song_id uuid,
        PRIMARY KEY (id, title, album, artist)
      );
      CREATE TABLE uk.playlists (
        id uuid,
        title text,
        album text,
        artist text,
        song_id uuid,
        PRIMARY KEY (id, title, album, artist)
      );
      INSERT INTO french.playlists (id, song_id, artist, title, album)
      VALUES (
        62c36092-82a1-3a00-93d1-46196ee77204,
        756716f7-2e54-4715-9f00-91dcbea6cf50,
        'Joséphine Baker',
        'La Petite Tonkinoise',
        'Bye Bye Blackbird'
      );
      INSERT INTO german.playlists (id, song_id, artist, title, album)
      VALUES (
        62c36092-82a1-3a00-93d1-46196ee77204,
        f6071e72-48ec-4fcb-bf3e-379c8a696488,
        'Willi Ostermann',
        'Die Mösch',
        'In Gold'
      );
      INSERT INTO uk.playlists (id, song_id, artist, title, album)
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
      $cluster          = Cassandra::cluster()
                            ->withContactPoints('127.0.0.1')
                            ->build();
      $french_session   = $cluster->connect("french");
      $german_session   = $cluster->connect("german");
      $uk_session       = $cluster->connect("uk");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM playlists");

      $row = $french_session->execute($statement);
      echo "French session contains " . $row->count() . " rows\n";
      $row = $german_session->execute($statement);
      echo "German session contains " . $row->count() . " rows\n";
      $row = $uk_session->execute($statement);
      echo "UK session contains " . $row->count() . " rows";
      """
    When it is executed
    Then its output should contain:
      """
      French session contains 1 rows
      German session contains 1 rows
      UK session contains 1 rows
      """
