#!/usr/bin/env bash

composer self-update
composer validate
composer install
composer require -W doctrine/couchdb:dev-master phpfastcache/phpssdb:~1.1 predis/predis:~1.1 mongodb/mongodb:~1.10 triagens/arangodb:~3.8 aws/aws-sdk-php:~3.2 google/cloud-firestore:~1.20 solarium/solarium:~6.1
