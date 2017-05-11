# DataStax PHP Driver for Apache Cassandra

[![Build Status: Linux](https://travis-ci.org/datastax/php-driver.svg)](https://travis-ci.org/datastax/php-driver)
[![Build Status: Windows](https://ci.appveyor.com/api/projects/status/8vrxpkfl4xm2f3nm?svg=true)](https://ci.appveyor.com/project/DataStax/php-driver)

A modern, [feature-rich](features) and highly tunable PHP client library for [Apache Cassandra](http://cassandra.apache.org/) (1.2+) and [DataStax Enterprise](http://www.datastax.com/products/products-index) (3.1+) using exclusively Cassandra's binary protocol and Cassandra Query Language v3.

This is a wrapper around [the DataStax C/C++ Driver for Apache Cassandra and DataStax Enterprise](http://datastax.github.io/cpp-driver/).

* Binaries: [http://downloads.datastax.com/php-driver/1.2.0](http://downloads.datastax.com/php-driver/1.2.0/)
* Docs: [http://datastax.github.io/php-driver/](http://datastax.github.io/php-driver/)
* Code: [https://github.com/datastax/php-driver](https://github.com/datastax/php-driver)
* Jira: [https://datastax-oss.atlassian.net/browse/PHP](https://datastax-oss.atlassian.net/browse/PHP)
* Mailing List: [https://groups.google.com/a/lists.datastax.com/forum/#!forum/php-driver-user](https://groups.google.com/a/lists.datastax.com/forum/#!forum/php-driver-user)
* IRC: #datastax-drivers on [irc.freenode.net](http://freenode.net>)
* Twitter: Follow the latest news about DataStax Drivers - [@avalanche123](http://twitter.com/avalanche123), [@al3xandru](https://twitter.com/al3xandru)

## What's new in v1.2.0:

* Full support for Apache Cassandra 2.2 and 3.0+
* Support for [`tinyint` and `smallint`](http://datastax.github.io/php-driver/features/datatypes/#using-cassandra-the-tinyint-and-smallint-types)
* Support for [`date`](http://datastax.github.io/php-driver/features/datatypes/#using-cassandra-date-type) and [`time`](http://datastax.github.io/php-driver/features/http://localhost:8000/features/datatypes/#using-cassandra-time-type)
* Support for [user-defined function and aggregate](http://datastax.github.io/php-driver/features/function_and_aggregate_metadata) metadata
* Support for [secondary index](http://datastax.github.io/php-driver/features/secondary_index_metadata) and [materialize view](http://datastax.github.io/php-driver/features/function_and_aggregate_metadata) metadata

## Feedback Requested

**Help us focus our efforts!** [Provide your input](http://goo.gl/forms/HbSiIJ2tLP) on the PHP Driver Platform and Runtime Survey (we kept it short).

## Quick Start

```php
<?php
$cluster   = Cassandra::cluster()                 // connects to localhost by default
                 ->build();
$keyspace  = 'system';
$session   = $cluster->connect($keyspace);        // create session, optionally scoped to a keyspace
$statement = new Cassandra\SimpleStatement(       // also supports prepared and batch statements
    'SELECT keyspace_name, columnfamily_name FROM schema_columnfamilies'
);
$future    = $session->executeAsync($statement);  // fully asynchronous and easy parallel execution
$result    = $future->get();                      // wait for the result, with an optional timeout

foreach ($result as $row) {                       // results and rows implement Iterator, Countable and ArrayAccess
    printf("The keyspace %s has a table called %s\n", $row['keyspace_name'], $row['columnfamily_name']);
}
```

## Installation

```bash
pecl install cassandra
```

[Read detailed instructions on building and installing the extension](https://github.com/datastax/php-driver/blob/master/ext/README.md)

## Compatibility

This driver works exclusively with the Cassandra Query Language v3 (CQL3) and
Cassandra's native protocol. The current version works with:

* Apache Cassandra versions 1.2, 2.0, 2.1, 2.2 and 3.0+
* DataStax Enterprise 3.1, 3.2, 4.0 and 4.5
* PHP 5.5+ and PHP 7.0+
* Compilers: GCC 4.1.2+, Clang 3.4+, and MSVC 2010/2012/2013/2015

## Contributing

[Read our contribution policy](https://github.com/datastax/php-driver/blob/master/CONTRIBUTING.md) for a detailed description of the process.

## Code examples

The DataStax PHP Driver uses the amazing [Behat Framework](http://docs.behat.org/)
for both end-to-end, or acceptance, testing and documentation. All of the features
supported by the driver have appropriate acceptance tests with [easy-to-copy code
examples in the `features/` directory](https://github.com/datastax/php-driver/tree/master/features).

## Running tests

For your convenience a `Vagrantfile` with configuration ready for testing is
available. To execute tests, run the following:

```bash
git clone https://github.com/datastax/php-driver.git
cd php-driver
git submodule update --init
vagrant up
vagrant ssh
```

Once you've logged in to the vagrant VM, run:

```bash
cd /usr/local/src/php-driver
./bin/behat
./bin/phpunit
```

## Copyright

Copyright 2015-2016 DataStax, Inc.

Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with the License. You may obtain a copy of the License at

[http://www.apache.org/licenses/LICENSE-2.0](http://www.apache.org/licenses/LICENSE-2.0)

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
