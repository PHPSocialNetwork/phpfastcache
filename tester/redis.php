<?php
$redis = new Redis();

$redis->connect('127.0.0.1', 6379);
$redis->connect('127.0.0.1'); // port 6379 by default
$redis->connect('127.0.0.1', 6379, 2.5); // 2.5 sec timeout.
$redis->connect('/tmp/redis.sock'); // unix domain socket.
$redis->connect('127.0.0.1', 6379, 1, NULL, 100); // 1 sec timeout, 100ms delay between reconnection attempts.