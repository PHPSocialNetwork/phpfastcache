@cassandra-version-2.0
Feature: Result paging

  Starting with Cassandra native protocol v2 (used by Apache Cassandra 2.0),
  paging through query results is allowed.

  Page size can be specified by setting the `$pageSize` attribute of
  `Cassandra\ExecutionOptions` or cluster-wide, using
  `Cassandra\Cluster\Buidler::withDefaultPageSize()`.

  Once a `Cassandra\Rows` object has been received, next page can be retrieved
  using `Cassandra\Rows::nextPage()` or `Cassandra\Rows::nextPageAsync()`
  methods for synchronous and asynchronous next page retrieval accordingly.

  To check if the next page is available, use `Cassandra\Rows::isLastPage()`

  Background:
    Given a running Cassandra cluster
    And the following schema:
      """cql
      CREATE KEYSPACE simplex WITH replication = {
        'class': 'SimpleStrategy',
        'replication_factor': 1
      };
      USE simplex;
      CREATE TABLE paging_entries (key text, value int, PRIMARY KEY(key, value));
      INSERT INTO paging_entries (key, value) VALUES ('a', 0);
      INSERT INTO paging_entries (key, value) VALUES ('b', 1);
      INSERT INTO paging_entries (key, value) VALUES ('c', 2);
      INSERT INTO paging_entries (key, value) VALUES ('d', 3);
      INSERT INTO paging_entries (key, value) VALUES ('e', 4);
      INSERT INTO paging_entries (key, value) VALUES ('f', 5);
      INSERT INTO paging_entries (key, value) VALUES ('g', 6);
      INSERT INTO paging_entries (key, value) VALUES ('h', 7);
      INSERT INTO paging_entries (key, value) VALUES ('i', 8);
      INSERT INTO paging_entries (key, value) VALUES ('j', 9);
      INSERT INTO paging_entries (key, value) VALUES ('k', 10);
      INSERT INTO paging_entries (key, value) VALUES ('l', 11);
      INSERT INTO paging_entries (key, value) VALUES ('m', 12);
      """

  Scenario: Paging through results synchronously
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM paging_entries");
      $options   = new Cassandra\ExecutionOptions(array('page_size' => 5));
      $rows      = $session->execute($statement, $options);

      while (true) {
          echo "entries in page: " . $rows->count() . "\n";

          foreach ($rows as $row) {
              echo "key: " . $row['key'] . ", value: " . $row['value'] . "\n";
          }

          if ($rows->isLastPage()) {
              break;
          }

          $rows = $rows->nextPage();
      }
      """
    When it is executed
    Then its output should contain:
      """
      entries in page: 5
      key: a, value: 0
      key: c, value: 2
      key: m, value: 12
      key: f, value: 5
      key: g, value: 6
      entries in page: 5
      key: e, value: 4
      key: d, value: 3
      key: h, value: 7
      key: l, value: 11
      key: j, value: 9
      entries in page: 3
      key: i, value: 8
      key: k, value: 10
      key: b, value: 1
      """

  Scenario: Accessing page info after loading next one
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM paging_entries");
      $options   = new Cassandra\ExecutionOptions(array('page_size' => 10));
      $rows      = $session->execute($statement, $options);

      $firstPageRows = $session->execute($statement, $options);
      echo $firstPageRows->isLastPage() ? "1: last\n" : "1: not last\n";

      $secondPageRows = $firstPageRows->nextPage();
      echo $firstPageRows->isLastPage() ? "1: last\n" : "1: not last\n";
      echo $secondPageRows->isLastPage() ? "2: last\n" : "2: not last\n";

      echo "entries in page 1: " . $firstPageRows->count() . "\n";
      echo "entries in page 2: " . $secondPageRows->count() . "\n";
      """
    When it is executed
    Then its output should contain:
      """
      1: not last
      1: not last
      2: last
      entries in page 1: 10
      entries in page 2: 3
      """

  @paging_state_token
  Scenario: Use paging state token to get next result
    Given the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect("simplex");
      $statement = new Cassandra\SimpleStatement("SELECT * FROM paging_entries");
      $options = array('page_size' => 2);
      $result = $session->execute($statement, new Cassandra\ExecutionOptions($options));

      foreach ($result as $row) {
        printf("key: '%s' value: %d\n", $row['key'], $row['value']);
      }

      while ($result->pagingStateToken()) {
          $options = array(
              'page_size' => 2,
              'paging_state_token' => $result->pagingStateToken()
          );

          $result = $session->execute($statement, new Cassandra\ExecutionOptions($options));

          foreach ($result as $row) {
            printf("key: '%s' value: %d\n", $row['key'], $row['value']);
          }
      }
      """
    When it is executed
    Then its output should contain:
      """
      key: 'a' value: 0
      key: 'c' value: 2
      key: 'm' value: 12
      key: 'f' value: 5
      key: 'g' value: 6
      key: 'e' value: 4
      key: 'd' value: 3
      key: 'h' value: 7
      key: 'l' value: 11
      key: 'j' value: 9
      key: 'i' value: 8
      key: 'k' value: 10
      key: 'b' value: 1
      """
