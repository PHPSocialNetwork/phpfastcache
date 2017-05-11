@SSL
Feature: SSL encryption

  PHP Driver supports SSL encryption.

  Scenario: Connecting without SSL encryption
    Given a running cassandra cluster with SSL encryption
    And the following example:
      """php
      <?php
      $cluster = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->build();

      try {
          $session = $cluster->connect();
      } catch (Cassandra\Exception\RuntimeException $e) {
          echo "Connection failure\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      Connection failure
      """

  Scenario: Connecting with basic SSL encryption
    Given a running cassandra cluster with SSL encryption
    And the following example:
      """php
      <?php
      $ssl     = Cassandra::ssl()
                     ->withVerifyFlags(Cassandra::VERIFY_NONE)
                     ->build();
      $cluster = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->withSSL($ssl)
                     ->build();

      try {
          $session = $cluster->connect();
          echo "Connection success\n";
      } catch (Cassandra\Exception\RuntimeException $e) {
          echo "Connection failure\n";
      }
      """
    When it is executed
    Then its output should contain:
      """
      Connection success
      """

  Scenario: Connecting with certificate verification
    Given a running cassandra cluster with SSL encryption
    And the following example:
      """php
      <?php
      $ssl     = Cassandra::ssl()
                     ->withVerifyFlags(Cassandra::VERIFY_PEER_CERT)
                     ->withTrustedCerts(getenv('SERVER_CERT'))
                     ->build();
      $cluster = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->withSSL($ssl)
                     ->build();

      try {
          $session = $cluster->connect();
          echo "Connection success\n";
      } catch (Cassandra\Exception\RuntimeException $e) {
          echo "Connection failure\n";
      }
      """
    When it is executed with trusted cert in the env
    Then its output should contain:
      """
      Connection success
      """

  Scenario: Connecting with client certificate verification
    Given a running cassandra cluster with client certificate verification
    And the following example:
      """php
      <?php
      $ssl     = Cassandra::ssl()
                     ->withVerifyFlags(Cassandra::VERIFY_PEER_CERT)
                     ->withTrustedCerts(getenv('SERVER_CERT'))
                     ->withClientCert(getenv('CLIENT_CERT'))
                     ->withPrivateKey(getenv('PRIVATE_KEY'), getenv('PASSPHRASE'))
                     ->build();
      $cluster = Cassandra::cluster()
                     ->withContactPoints('127.0.0.1')
                     ->withSSL($ssl)
                     ->build();

      try {
          $session = $cluster->connect();
          echo "Connection success\n";
      } catch (Cassandra\Exception\RuntimeException $e) {
          echo "Connection failure\n";
      }
      """
    When it is executed with trusted and client certs, private key and passphrase in the env
    Then its output should contain:
      """
      Connection success
      """
