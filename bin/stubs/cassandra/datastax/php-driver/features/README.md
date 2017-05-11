# Features

## Usage

### Specifying addresses of Cassandra nodes

[`withContactPoints()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withContactPoints) and [`withPort()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withPort) methods of the [`Cassandra\Cluster\Builder`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/) are used to specify IP addresses or hostnames and port number of the nodes in a given Cassandra cluster.

Note that you don't have to specify the addresses of all hosts in your cluster. Once the driver has established a connection to any host, it will perform auto-discovery and connect to all hosts in the cluster.

```php
<?php

$cluster = Cassandra::cluster()
               ->withContactPoints('10.0.1.24', 'example.com', 'localhost')
               ->withPort(9042)
               ->build();
$session = $cluster->connect();
```

### Discovering nodes in the cluster

After the initial connection to one of the hosts specified via `withContactPoints()` succeeds, the driver discovers the addresses and connects to all members of the cluster automatically. You can also see the nodes that the driver discovered by running `SELECT * FROM system.peers`.

### Persistent sessions

In order to limit the startup time and total number of connections to a Cassandra cluster, the PHP Driver enables persistent sessions by default. All cluster and sessions using the same initial configuration will be shared across requests when persistent sessions are enabled. You can toggle this setting using [`Cassandra\Cluster\Builder::withPersistentSessions()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withPersistentSessions).

```php
<?php

$cluster = Cassandra::cluster()
               ->withPersistentSessions(false)
               ->build();
$session = $cluster->connect();
```

Note that disabling persistent sessions will cause a significant slow down of cluster initialization as the connections will be forced to get re-established for every request.

Once persistent sessions are enabled, you can view how many of them are currently active. They will be exposed in the Cassandra extension section of `phpinfo()`.

Persistent sessions stay alive for the duration of the parent process, typically a php-fpm worker or apache worker. These sessions will be reused for all requests served by that worker process. Once a worker process has reached its end of life, sessions will get cleaned up automatically and will be re-create in the new process.

### Configuring load balancing policy

The PHP Driver comes with a variety of load balancing policies. By default it uses a combination of latency aware, token aware and data center aware round robin load balancing.

The token aware load balancing policy uses the same hashing algorithms as the Apache Cassandra to directly route the execution of prepared statements to the replica node, avoiding an additional network hop to/from the coordinator. You can toggle its usage with [`Cassandra\Cluster\Builder::withTokenAwareRouting()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withTokenAwareRouting).

```php
<?php

$cluster = Cassandra::cluster()
               ->withTokenAwareRouting(false)
               ->build();
$session = $cluster->connect();
```

The default datacenter aware round robin load balancing policy is configured to keep all traffic in the same datacenter. Upon connecting to a host from the initial list of contact points, the driver will consider that host's datacenter to be local. Only hosts from the same datacenter will be connected to and used for executing statements. You can override the name of the local datacenter. The number of hosts from remote datacenters that the driver may use and whether it should execute statements with local consistencies on those hosts in case none of the local hosts are available. All of that is configurable via [`Cassandra\Cluster\Builder::withDatacenterAwareRoundRobinLoadBalancingPolicy()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withDatacenterAwareRoundRobinLoadBalancingPolicy).

```php
<?php

$cluster = Cassandra::cluster()
               ->withDatacenterAwareRoundRobinLoadBalancingPolicy("us-west", 2, true)
               ->build();
$session = $cluster->connect();
```

You may disable datacenter awareness by calling [`Cassandra\Cluster\Builder::withRoundRobinLoadBalancingPolicy()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withRoundRobinLoadBalancingPolicy).

```php
<?php

$cluster = Cassandra::cluster()
               ->withRoundRobinLoadBalancingPolicy()
               ->build();
$session = $cluster->connect();
```

Finally, latency-aware routing ensures that requests are routed to the hosts that respond the fastest. You can switch it off via [`Cassandra\Cluster\Builder::withLatencyAwareRouting()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withLatencyAwareRouting).

```php
<?php

$cluster = Cassandra::cluster()
               ->withLatencyAwareRouting(false)
               ->build();
$session = $cluster->connect();
```

### Setting protocol version

The PHP driver will automatically negotiate native protocol version of TCP connections to the latest supported by both the driver and Apache Cassandra servers. It does this by attempting connection at the highest supported protocol version (currently 2) and negotiating it down upon unsupported version responses from the server.

In a scenario with an Apache Cassandra cluster consisting of nodes of mixed versions (e.g. 1.2.x and 2.0.x), this might pose problems as the driver could establish native protocol version to be 2, while some of the nodes don't support it, causing connections to the rest of the cluster to fail.

You can force the driver to start negotiation at a lower protocol version by using [`Cassandra\Cluster\Builder::withProtocolVersion()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withProtocolVersion).

```php
<?php

$cluster = Cassandra::cluster()
               ->withProtocolVersion(1)
               ->build();
$session = $cluster->connect();
```

### Tweaking driver's throughput

There are a few variables affecting the total throughput of the driver that can be tweaked client-side. The maximum number of requests that can be executed at the same time is calculated with the following formula:

```
inflight_requests = io_threads * requests_per_connection * maximum_number_of_connections_per_host * connected_hosts
```

Where `io_threads` by default is `1`, `requests_per_connection` for the currently supported protocol versions is `128`, `maximum_number_of_connections_per_host` by default is `2` and `connected_hosts` is the total number of hosts that can be connected to. This last variable depends on the load balancing policy used, data center aware policy only connects to the hosts in the same data center by default.

You can change the value of `io_threads` from the formula above by using [`Cassandra\Cluster\Builder::withIOThreads()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withIOThreads).

```php
<?php

$cluster = Cassandra::cluster()
               ->withIOThreads(4)
               ->build();
$session = $cluster->connect();
```

You can change the value of `maximum_number_of_connections_per_host` from the formula above by using [`Cassandra\Cluster\Builder::withConnectionsPerHost()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withConnectionsPerHost).

```php
<?php

$cluster = Cassandra::cluster()
               ->withConnectionsPerHost(4, 8)
               ->build();
$session = $cluster->connect();
```

### Disabling TCP nodelay

By default, the driver enables TCP nodelay (Nagle's algorithm) on all connections it uses. Disabling it is not recommended but possible via [`Cassandra\Cluster\Builder::withTCPNodelay()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withTCPNodelay).

```php
<?php

$cluster = Cassandra::cluster()
               ->withTCPNodelay(false)
               ->build();
$session = $cluster->connect();
```

### Enabling TCP keepalive

By default, TCP keepalive is disabled. It can be useful to make sure TCP connections are not silently dropped by a firewall or some other intermediary network device. You can enable it using [`Cassandra\Cluster\Builder::withTCPKeepalive()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withTCPKeepalive).

```php
<?php

// enable keepalive with a 10 second interval.

$cluster = Cassandra::cluster()
               ->withTCPKeepalive(10)
               ->build();
$session = $cluster->connect();
```

### Authenticating via `PasswordAuthenticator`

The PHP Driver supports Apache Cassandra's built-in password authentication mechanism. To enable it, use [`Cassandra\Cluster\Builder::withCredentials()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withCredentials).

```php
<?php

$cluster = Cassandra::cluster()
               ->withCredentials("username", "password")
               ->build();
$session = $cluster->connect();
```

### Enabling SSL encryption

The PHP Driver supports SSL encryption of network connections. You must configure [`Cassandra\SSLOptions`](http://datastax.github.io/php-driver/api/Cassandra/class.SSLOptions/) using the [`Cassandra\SSLOptions\Builder`](http://datastax.github.io/php-driver/api/Cassandra/SSLOptions/class.Builder/).

```php
<?php

$ssl     = Cassandra::ssl()
               ->withTrustedCerts('node1.pem', 'node2.pem')
               ->withVerifyFlags(Cassandra::VERIFY_PEER_CERT | Cassandra::VERIFY_PEER_IDENTITY)
               ->withClientCert('client.pem')
               ->withPrivateKey('id_rsa', 'passphrase')
               ->build()
$cluster = Cassandra::cluster()
               ->withSSL($ssl)
               ->build();
$session = $cluster->connect();
```

### Executing queries

You run CQL statements by passing them to [`Cassandra\Session::execute()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/).

```php
<?php

$result = $session->execute(new Cassandra\SimpleStatement('SELECT keyspace_name, columnfamily_name FROM system.schema_columnfamilies'));

foreach ($result as $row) {
    printf("The keyspace \"%s\" has a table \"%s\".\n", $row['keyspace_name'], $row['columnfamily_name']);
}
```

### Parameterized queries

**If you're using Cassandra 2.0 or later** you no longer have to build CQL strings when you want to insert a value in a query, there's a new feature that lets you bind values with regular statements:

```php
<?php

$session->execute(
    new Cassandra\SimpleStatement("UPDATE users SET age = ? WHERE user_name = ?"),
    new Cassandra\ExecutionOptions(array(
        'arguments' => array(41, 'Sam')
    ))
);
```

For frequently executed queries, it's strongly recommended to use prepared statements. As a rule of thumb, if your application is sending a request more than once, a prepared statement is almost always the right choice.

### Prepared statements

The driver supports prepared statements. Use [`Cassandra\Session::prepare()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.prepare) to create a [`Cassandra\PreparedStatement`](http://datastax.github.io/php-driver/api/Cassandra/class.PreparedStatement/) object, and then call [`Cassandra\Session::execute()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.execute).

```php
<?php

$statement = $session->prepare('INSERT INTO users (username, email) VALUES (?, ?)');

$session->execute($statement, new Cassandra\ExecutionOptions(array(
    'arguments' => array('avalanche123', 'bulat.shakirzyanov@datastax.com')
)));
```

A prepared statement can be run many times, but the CQL parsing will only be done once on each node. Use prepared statements for queries you run over and over again.

### Executing statements in parallel

With fully asynchronous API, it is very easy to run queries in parallel:

```php
<?php

$data = array(
    array(41, 'Sam'),
    array(35, 'Bob')
);

$statement = $session->prepare("UPDATE users SET age = ? WHERE user_name = ?");
$futures   = array();

// execute all statements in background
foreach ($data as $arguments) {
    $futures[]= $session->executeAsync($statement, new ExecutionOptions(array(
                    'arguments' => $arguments
                )));
}

// wait for all statements to complete
foreach ($futures as $future) {
    // we will not wait for each result for more than 5 seconds
    $future->get(5);
}
```

Note that it is not enough to simply create a [`Cassandra\Future`](http://datastax.github.io/php-driver/api/Cassandra/interface.Future/) by calling one of the `*Async()` methods, you must ensure that this future has enough time to be executed by calling [`Cassandra\Future::get()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Future/#method.get).

### Creating keyspaces and tables

There is no special facility for creating keyspaces and tables, they are created by executing CQL:

```php
<?php

$createKeyspace = new Cassandra\SimpleStatement(<<<EOD
CREATE KEYSPACE measurements
WITH replication = {
  'class': 'SimpleStrategy',
  'replication_factor': 1
}
EOD
);

$createTable = new Cassandra\SimpleStatement(<<<EOD
CREATE TABLE events (
  id INT,
  date DATE,
  comment VARCHAR,
  PRIMARY KEY (id)
)
EOD
);

$session->execute($createKeyspace);
$session->execute('USE measurements');
$session->execute($createTable);
```

You can also `ALTER` keyspaces and tables, and you can read more about that in the [CQL3 syntax documentation](https://github.com/apache/cassandra/blob/cassandra-2.0/doc/cql3/CQL.textile).

### Batch statements

**If you're using Cassandra 2.0 or later** you can build batch requests, either from simple or prepared statements. Batches must not contain any select statements, only `INSERT`, `UPDATE` and `DELETE` statements are allowed.

You can mix any combination of statements in a batch:

```php
<?php

$batch = new Cassandra\BatchStatement();

$statement = $session->prepare("UPDATE users SET name = ? WHERE user_id = ?");
$batch->add($statement, array('Sue', 'unicorn31'));

$statement = new Cassandra\SimpleStatement("UPDATE users SET age = 19 WHERE user_id = 'unicorn31'");
$batch->add($statement);

$statement = new Cassandra\SimpleStatement("INSERT INTO activity (user_id, what, when) VALUES (?, 'login', NOW())");
$batch->add($statement, array('unicorn31'));

$session->execute($batch);
```

Batches can have one of three different types: `logged`, `unlogged` or `counter`, where `logged` is the default. Their exact semantics are defined in the [Cassandra documentation](http://docs.datastax.com/en/cql/3.1/cql/cql_reference/batch_r.html), but this is how you specify which one you want:

```php
<?php

$batch = new Cassandra\BatchStatement(Cassandra::BATCH_LOGGED);
$batch = new Cassandra\BatchStatement(Cassandra::BATCH_UNLOGGED);
$batch = new Cassandra\BatchStatement(Cassandra::BATCH_COUNTER);
```

[Read more about `Cassandra\BatchStatement`](http://datastax.github.io/php-driver/api/Cassandra/class.BatchStatement/)

Cassandra 1.2 also supported batching, but only as a CQL feature, you had to build the batch as a string, and it didn't really play well with prepared statements.

### Result paging

**If you're using Cassandra 2.0** or later you can page your query results.

By default, a page size of 5000 will be used, you can override the default page size via [`Cassandra\Cluster\Builder::withDefaultPageSize()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withDefaultPageSize).

```php
<?php

$cluster = Cassandra::cluster()
               ->withDefaultPageSize(200)
               ->build();
$session = $cluster->connect();
```

You can also override the page size on a per-execute basis by adding the `page_size` option to [`Cassandra\ExecutionOptions`](http://datastax.github.io/php-driver/api/Cassandra/class.ExecutionOptions/):

```php
<?php

$statement = new Cassandra\SimpleStatement("SELECT * FROM large_table WHERE id = 'partition_with_lots_of_data'");
$result    = $session->execute($statement, new Cassandra\ExecutionOptions(array('page_size' => 100)));

while ($result) {
    foreach ($result as $row) {
        var_dump($row);
    }
    $result = $result->nextPage();
}
```

[Read more about `Cassandra\Rows::nextPage()`](http://datastax.github.io/php-driver/api/Cassandra/class.Rows/#method.nextPage)

### Consistency

You can specify the default consistency to use for statements execution when you create a new `Cassandra\Cluster`:

```php
<?php

$cluster = Cassandra::cluster()
               ->withDefaultConsistency(Cassandra::CONSISTENCY_LOCAL_QUORUM)
               ->build();
$session = $cluster->connect();
```

[Read more `Cassandra\Cluster\Builder::withDefaultConsistency()`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withDefaultConsistency)

Consistency can also be passed via `Cassandra\ExecutionOptions`.

```php
<?php

$session->execute(
    new Cassandra\SimpleStatement('SELECT * FROM users'),
    new Cassandra\ExecutionOptions(array('consistency' => Cassandra::CONSISTENCY_LOCAL_QUORUM))
);

$statement = $session->prepare('SELECT * FROM users');
$session->execute($statement, new Cassandra\ExecutionOptions(array(
    'consistency' => Cassandra::CONSISTENCY_LOCAL_QUORUM
)));

$batch = new Cassandra\BatchStatement();
$batch->add(new Cassandra\SimpleStatement("UPDATE users SET email = 'sue@foobar.com' WHERE id = 'sue'"));
$batch->add(new Cassandra\SimpleStatement("UPDATE users SET email = 'tom@foobar.com' WHERE id = 'tom'"));
$session->execute($batch, new Cassandra\ExecutionOptions(array(
    'consistency' => Cassandra::CONSISTENCY_LOCAL_QUORUM
)));
```

[Read more about `Cassandra\ExecutionOptions`](http://datastax.github.io/php-driver/api/Cassandra/class.ExecutionOptions/)

[Read more about `Cassandra\Session::execute()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.execute)

The default consistency level unless you've set it yourself is `Cassandra::CONSISTENCY_LOCAL_ONE`.

Consistency is ignored for `USE`, `TRUNCATE`, `CREATE` and `ALTER` statements, and some (like `Cassandra::CONSISTENCY_ANY`) aren't allowed in all situations.

### Schema Metadata

The DataStax PHP driver exposes schema metadata via [`Cassandra\Schema`](http://datastax.github.io/php-driver/api/Cassandra/interface.Schema/) object, available using [`Cassandra\Session::schema()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.schema).
Schema metadata includes information about keyspace, tables and columns and it automatically kept up-to-date with the Cassandra cluster.

```php
<?php

$schema = $session->schema();

foreach ($schema->keyspaces() as $keyspace) {
    printf("Keyspace: %s\n", $keyspace->name());
    printf("    Replication Strategy: %s\n", $keyspace->replicationClassName());
    printf("    Replication Options:\n");
    $options = $keyspace->replicationOptions();
    $keys    = $options->keys();
    $values  = $options->values();
    foreach (array_combine($keys, $values) as $key => $value) {
        printf("        %s: %s\n", $key, $value);
    }
    printf("    Durable Writes:       %s\n", $keyspace->hasDurableWrites() ? 'true' : 'false');

    foreach ($keyspace->tables() as $table) {
        printf("    Table: %s\n", $table->name());
        printf("        Comment:                    %s\n", $table->comment());
        printf("        Read Repair Chance:         %f\n", $table->readRepairChance());
        printf("        Local Read Repair Chance:   %f\n", $table->localReadRepairChance());
        printf("        GC Grace Seconds:           %d\n", $table->gcGraceSeconds());
        printf("        Caching:                    %s\n", $table->caching());
        printf("        Bloom Filter FP Chance:     %f\n", $table->bloomFilterFPChance());
        printf("        Memtable Flush Period Ms:   %d\n", $table->memtableFlushPeriodMs());
        printf("        Default Time To Live:       %d\n", $table->defaultTTL());
        printf("        Speculative Retry:          %s\n", $table->speculativeRetry());
        printf("        Index Interval:             %d\n", $table->indexInterval());
        printf("        Compaction Strategy:        %s\n", $table->compactionStrategyClassName());
        printf("        Populate IO Cache On Flush: %s\n", $table->populateIOCacheOnFlush() ? 'yes' : 'no');
        printf("        Replicate On Write:         %s\n", $table->replicateOnWrite() ? 'yes' : 'no');
        printf("        Max Index Interval:         %d\n", $table->maxIndexInterval());
        printf("        Min Index Interval:         %d\n", $table->minIndexInterval());

        foreach ($table->columns() as $column) {
            printf("        Column: %s\n", $column->name());
            printf("            Type:          %s\n", $column->type());
            printf("            Order:         %s\n", $column->isReversed() ? 'desc' : 'asc');
            printf("            Frozen:        %s\n", $column->isFrozen() ? 'yes' : 'no');
            printf("            Static:        %s\n", $column->isStatic() ? 'yes' : 'no');

            if ($column->indexName()) {
                printf("            Index:         %s\n", $column->indexName());
                printf("            Index Options: %s\n", $column->indexOptions());
            }
        }
    }
}
```

**NOTE** A new instance of [`Cassandra\Schema`](http://datastax.github.io/php-driver/api/Cassandra/interface.Schema/) is returned each time [`Cassandra\Session::schema()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.schema) is called. This instance is a simple value object and its information, such as keyspaces, tables and columns will not be kept up-to-date with the state of the cluster. In order to obtain the latest schema metadata, you have to call [`Cassandra\Session::schema()`](http://datastax.github.io/php-driver/api/Cassandra/interface.Session/#method.schema) again.

### Data Types

The PHP driver for Apache Cassandra supports [a variety of datatypes](http://datastax.github.io/php-driver/features/datatypes/).

You can also use the rich type metadata API to define and inspect types, as well as validate data objects.

The example below defines and creates a [`Cassandra\Map`](http://datastax.github.io/php-driver/api/Cassandra/class.Map/) using [`Cassandra\Type`](http://datastax.github.io/php-driver/api/Cassandra/interface.Type/) interface.

```php
<?php

$map = Cassandra\Type::map(Cassandra\Type::varchar(), Cassandra\Type::int())
                     ->create('a', 1, 'b', 2, 'c', 3, 'd', 4);

var_dump(array_combine($map->keys(), $map->values()));
```

**NOTE** The `create()` method or various types validates and coerces provided values into the target type.

### Logging

You can configure the location of the log file for the driver as well as the log level using the following `php.ini` settings:

```ini
[cassandra]
cassandra.log=syslog
cassandra.log_level=INFO
```

You can specify any file path as `cassandra.log`.

The special value `syslog` can be used to for the driver to use syslog for logging. Syslog is only supported on \*nix systems.

The possible log levels are:

* CRITICAL
* ERROR
* WARN
* INFO
* DEBUG
* TRACE

Most of the logging will be when the driver connects and discovers new nodes, when connections fail and so on. The logging is designed to not cause much overhead and only relatively rare events are logged (e.g. normal requests are not logged).

## Architecture

The PHP Driver follows the architecture of [the C/C++ Driver](http://datastax.github.io/cpp-driver/topics/#architecture) that it wraps.

### Persistent sessions

By default, the driver uses persistent sessions to prevent each request from creating completely new TCP connections to a Cassandra cluster. You can toggle this functionality using [`Cassandra\Cluster\Builder::withPersistentSessions`](http://datastax.github.io/php-driver/api/Cassandra/Cluster/class.Builder/#method.withPersistentSessions)
