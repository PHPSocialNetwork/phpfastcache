Contributing to PhpFastCache
========================

Please note that this project is released with a
[Contributor Code of Conduct](http://contributor-covenant.org/version/1/4/).
By participating in this project you agree to abide by its terms.

Reporting Issues
----------------

When reporting issues, please try to be as descriptive as possible, and include
as much relevant information as you can. A step by step guide on how to
reproduce the issue will greatly increase the chances of your issue being
resolved in a timely manner.

Contributing policy
-------------------

Our contributing policy is described in our [Coding Guideline](https://github.com/PHPSocialNetwork/phpfastcache/blob/v7/CODING_GUIDELINE.md)

Developer notes
-------------------
If you want to contribute to the repository you will need to install/configure some things first.

To run tests follow the steps:
- Run `./bin/ci/scripts/install_dependencies.sh`
- Run `./vendor/bin/phpcs lib/  --report=summary`
- Run `./vendor/bin/phpmd lib/ ansi phpmd.xml`
- Run `./vendor/bin/phpstan analyse lib/ -l 2 -c phpstan_lite.neon 2>&1`
- Run `php -f ./bin/ci/run_tests.php`

The last command will run all the tests including the quality tests (phpmd, phpcs, phpstan).
If an error appears, fix it then you can submit your pull request.

Don't worry if you don't have some services installed like "memcached", "couchdb", "couchbase", "mongodb", etc.
The tests will be skipped on your computer but will be running in Github CI/Travis CI.
