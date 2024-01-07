#!/usr/bin/env bash

composer self-update
composer validate
composer install
composer require -W phpfastcache/phpssdb:^1.2 predis/predis:^2.0
