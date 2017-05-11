# Contributing

## Reporting bugs

It is inevitable that code will have bugs. There are a few things you can do to
help the maintainer to fix the bug that you've discovered quicker:

* [Use the PHP Driver Jira](https://datastax-oss.atlassian.net/projects/PHP) to report all issues and bugs.
* Include the version of the Driver, PHP and Cassandra or DSE in your Jira ticket.
* Include the Driver dependency versions as well (e.g. libuv, C/C++ Driver, ...etc)
* Include a complete stack trace of the failure as well as any available logs.
* Include any additional information you think is relevant - the description of
  your setup, any non-default Driver configuration, etc.
* Write a failing test. The PHP Driver uses [PHPUnit test framework](https://phpunit.de/). A reliably
  failing test is the fastest way to demonstrate and fix a problem.

## Pull requests

If you're able to fix a bug yourself, you can [fork the repository](https://help.github.com/articles/fork-a-repo/) and [submit a pull request](https://help.github.com/articles/using-pull-requests/) with the fix.

To protect DataStax and the community, all contributors are required to [sign the DataStax Contribution License Agreement](http://cla.datastax.com/). The process is completely electronic and should only take a few minutes.

## Style guide

There are 2 distinct coding styles used in the repository. Depending on which part of the codebase you're going to touch, you need to follow one or the other.

### PHP coding style

The API stubs provided by the extension are following [the Symfony 2 coding standard](http://symfony.com/doc/current/contributing/code/standards.html). The only exception are empty method stubs, those have opening and closing brace following the closing parenthesis on the same line.

### C coding style

The main difference here is the spacing, the C code uses 2 spaces for indendation, while the PHP code - 4. For your convenience, a `.clang-format` file is available in the repository's root directory. Using `clang-format`, you can format source files to match the coding style almost completely:

```bash
clang-format -style=file -i ext/php_cassandra.c
```
