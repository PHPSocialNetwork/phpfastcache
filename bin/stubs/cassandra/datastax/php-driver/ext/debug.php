<?php

if (!extension_loaded('cassandra')) die("Unable to load cassandra extension\n");

try {
  $cluster = Cassandra::cluster()
                 ->withContactPoints('127.0.0.1')
                 ->build();
  $session = $cluster->connect();
  echo "connection success\n";
} catch (Cassandra\Exception $e) {
  echo "connection failure\n";
  echo get_class($e) . ": " . $e->getMessage() . "\n";
}
