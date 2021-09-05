#!/bin/bash

set -e

wget --no-check-certificate https://github.com/ideawu/ssdb/archive/master.zip
unzip -q master
cd ssdb-master

make > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "[OK] Make command succeeded, running server..."
    ./ssdb-server -d ssdb.conf
else
    echo "[KO] Make command failed, the server will NOT be running"
fi
