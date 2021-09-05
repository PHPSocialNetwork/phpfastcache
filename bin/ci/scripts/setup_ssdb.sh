#!/bin/bash

set -e

wget --no-check-certificate https://github.com/ideawu/ssdb/archive/master.zip
unzip -q master
cd ssdb-master
make
./ssdb-server -d ssdb.conf
