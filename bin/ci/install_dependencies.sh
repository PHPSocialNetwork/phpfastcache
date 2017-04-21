#!/usr/bin/env bash

composer self-update;
composer install --ignore-platform-reqs;
composer require "phpfastcache/couchdb:~1.0.0" "phpfastcache/phpssdb:~1.0.0" "predis/predis:~1.1.0" "mongodb/mongodb:^1.1" --ignore-platform-reqs;
