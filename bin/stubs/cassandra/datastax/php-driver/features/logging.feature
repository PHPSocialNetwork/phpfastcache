Feature: Logging

  PHP Driver support file logging and system logging

  Background:
    Given a running Cassandra cluster

  Scenario: Changing PHP driver logging configuration
     Given the following logger settings:
      """ini
      cassandra.log=feature-logging.log
      cassandra.log_level=TRACE
      """
    And the following example:
      """php
      <?php
      $cluster   = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();
      $session   = $cluster->connect();
      """
    When it is executed
    Then a log file "feature-logging.log" should exist
    And the log file "feature-logging.log" should contain "TRACE"
