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
1) Run `./bin/ci/scripts/install_dependencies.sh`
2) Run `./vendor/bin/phpcs lib/  --report=summary`
3) Run `./vendor/bin/phpmd lib/ ansi phpmd.xml`
4) Run `./vendor/bin/phpstan analyse lib/ -l 2 -c phpstan_lite.neon 2>&1`
5) Run `php -f ./bin/ci/run_tests.php`

If you are on Windows environment simply run the file `quality.bat` located at the root of the project to run the step 2, 3 and 4 in once.

The last command will run all the unit tests of the project.
If an error appears, fix it then you can submit your pull request.

Some tests will be skipped if you don't have special dependencies installed (Arangodb, Couchbase, Couchdb, Firestore credential and SDK, Dynamodb credential, etc.).\
So don't worry if those tests are skipped as long as they **pass** on the Github and Travis CIs.
