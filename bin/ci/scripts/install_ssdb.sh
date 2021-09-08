#!/bin/bash

set -e

wget --no-check-certificate -O master.zip https://codeload.github.com/ideawu/ssdb/zip/master
unzip -q master
cd ssdb-master

make > /dev/null 2>&1
if [ $? -eq 0 ]; then
    echo "[OK] Make command succeeded, running server..."
    ./ssdb-server -d ssdb.conf
else
    echo "[KO] Make command failed, the server will NOT be running"
fi
