#!/bin/bash

set -e

export COUCHDB_HOST=localhost
export COUCHDB_PORT=5984
export COUCHDB_VERSION=2.3.1

echo "# Running Docker couchdb image"
docker run -d -p $COUCHDB_PORT:$COUCHDB_PORT couchdb:$COUCHDB_VERSION --with-haproxy --with-admin-party-please -n 1

check_db() {
  # Allow 4xx errors not to break the connexion check
  curl --silent http://$COUCHDB_HOST:$COUCHDB_PORT > /dev/null
  echo $?
}

# Echo with
i=1
numbered_echo() {
  echo "[$i] $*"
  i=$(($i+1))
}

until [[ $(check_db) = 0 ]]; do
  >&2 numbered_echo "Waiting for Couchdb Server to be available"
  if [[ i  -gt 10 ]]; then
    echo "Wait time exceeded, aborting".
    break;
   fi
  sleep 1
done
echo "# Couchbdb Server Online"

echo "# Creating Couchdb admin"
curl -s -X PUT -s -o /dev/null -w "HTTP %{response_code}" $COUCHDB_HOST:${COUCHDB_PORT}/_node/_local/_config/admins/admin -d '"travis"'
# curl -X PUT $COUCHDB_HOST:5984/phpfastcache_test_database

printf "\n\n\n"
