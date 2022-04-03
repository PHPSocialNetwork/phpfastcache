#!/bin/bash

set -e

export SOLR_PORT=8983
export SOLR_HOST=127.0.0.1
export SOLR_VERSION=8.11.1
export SOLR_CORE=phpfastcache

download() {
    FILE="$2.tgz"
    if [ -f $FILE ];
    then
       echo "File $FILE exists."
       tar -zxf $FILE
    else
       echo "File $FILE does not exist. Downloading solr from $1..."
       curl -O $1
       tar -zxf $FILE
    fi
    echo "Downloaded!"
}

is_solr_up(){
    echo "Checking if solr is up on http://localhost:$SOLR_PORT/solr/admin/cores"
    http_code=`echo $(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$SOLR_PORT/solr/admin/cores")`
    return `test $http_code = "200"`
}

wait_for_solr(){
    while ! is_solr_up; do
        sleep 3
    done
}

run_solr() {
    dir_name=$1
    ./$dir_name/bin/solr -p $SOLR_PORT -h $SOLR_HOST
    wait_for_solr
    ./$dir_name/bin/solr create_core -c $SOLR_CORE -p $SOLR_PORT
    echo "Started"
}

download_and_run() {
	version=$1
  url="http://archive.apache.org/dist/lucene/solr/${version}/solr-${version}.tgz"
  dir_name="solr-${version}"
  download $url $dir_name

  run_solr $dir_name
}



download_and_run $SOLR_VERSION
