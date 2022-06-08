#!/bin/bash

set -e

export CB_VERSION=7.0.2
export CB_RELEASE_URL=https://packages.couchbase.com/releases
export CB_PACKAGE=couchbase-server-community_7.0.2-ubuntu18.04_amd64.deb

# Community Edition requires that all nodes provision all services or data service only
export SERVICES="kv,n1ql,index,fts"

export USERNAME=test
export PASSWORD=phpfastcache

export MEMORY_QUOTA=256
export INDEX_MEMORY_QUOTA=256
export FTS_MEMORY_QUOTA=256


# Check if couchbase server is up
check_db() {
  curl --silent http://127.0.0.1:8091/pools > /dev/null
  echo $?
}

# Variable used in echo
i=1
# Echo with
numbered_echo() {
  echo "[$i] $*"
  i=$(($i+1))
}

echo "# Prepare Couchbase dependencies"
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys A3FAA648D9223EDA
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 1616981CC4A088B2
echo "deb https://packages.couchbase.com/ubuntu bionic bionic/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
echo "deb https://packages.couchbase.com/clients/c/repos/deb/ubuntu1804 bionic bionic/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
sudo apt-get update
sudo apt-get install -yq libcouchbase3 libcouchbase-dev build-essential libssl1.0.0 runit wget python-httplib2 chrpath tzdata lsof lshw sysstat net-tools numactl

echo "# Downloading couchbase"
wget -q -N $CB_RELEASE_URL/$CB_VERSION/$CB_PACKAGE || echo "Failed to download Couchbase $CB_RELEASE_URL"
sudo dpkg -i ./$CB_PACKAGE && rm -f ./$CB_PACKAGE

# Wait until it's ready
until [[ $(check_db) = 0 ]]; do
  >&2 numbered_echo "Waiting for Couchbase Server to be available"
  sleep 1
done

echo "# Couchbase Server Online"
echo "# Starting setup process"
echo "# 1) Setting up memory"
curl -i "http://127.0.0.1:8091/pools/default" \
    -d memoryQuota=${MEMORY_QUOTA} \
    -d indexMemoryQuota=${INDEX_MEMORY_QUOTA} \
    -d ftsMemoryQuota=${FTS_MEMORY_QUOTA}

echo "# 2) Setting up services"
curl -i "http://127.0.0.1:8091/node/controller/setupServices" \
    -d services="${SERVICES}"

echo "# 3) Setting up user credentials"
curl -i "http://127.0.0.1:8091/settings/web" \
    -d port=8091 \
    -d username=${USERNAME} \
    -d password=${PASSWORD}

echo "# 4) Setting up the bucket"
curl -i "http://127.0.0.1:8091/pools/default/buckets" \
    -d name=phpfastcache \
    -d ramQuotaMB=256 \
    -u ${USERNAME}:${PASSWORD} \


echo "# Couchbase running successfully" 
