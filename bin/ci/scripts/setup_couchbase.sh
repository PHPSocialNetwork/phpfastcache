#!/bin/bash

# Not possible to run docker container in travis...
# https://docs.travis-ci.com/user/database-setup/#starting-services => Couchbase not yet available

set -e
export COUCHBASE_OS_VERSION=$(lsb_release -sr)

if [[ COUCHBASE_OS_VERSION == "16."* ]]; then
    export CB_VERSION=6.6.0
    export CB_PACKAGE=couchbase-server-community_6.6.0-ubuntu16.04_amd64.deb
else
    export CB_VERSION=7.0.0
    export CB_PACKAGE=couchbase-server-community_7.0.0-ubuntu18.04_amd64.deb
fi

export CB_RELEASE_URL=https://packages.couchbase.com/releases

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
  echo "[$i] $@"
  i=`expr $i + 1`
}

echo "# Prepare Couchbase dependencies"
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys A3FAA648D9223EDA
sudo apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 1616981CC4A088B2
if [[ COUCHBASE_OS_VERSION == "16."* ]]; then
    echo "deb https://packages.couchbase.com/ubuntu xenial xenial/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
    echo "deb https://packages.couchbase.com/clients/c/repos/deb/ubuntu1604 xenial xenial/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
else
    echo "deb https://packages.couchbase.com/ubuntu bionic bionic/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
    echo "deb https://packages.couchbase.com/clients/c/repos/deb/ubuntu1804 bionic bionic/main" | sudo tee /etc/apt/sources.list.d/couchbase.list
fi

sudo apt-get update
sudo apt-get install -yq libcouchbase3 libcouchbase-dev build-essential libssl1.0.0 runit wget python-httplib2 chrpath tzdata lsof lshw sysstat net-tools numactl

echo "# Downloading couchbase"
wget -q -N $CB_RELEASE_URL/$CB_VERSION/$CB_PACKAGE
sudo dpkg -i ./$CB_PACKAGE && rm -f ./$CB_PACKAGE

# Wait until it's ready
until [[ $(check_db) = 0 ]]; do
  >&2 numbered_echo "Waiting for Couchbase Server to be available"
  sleep 1
done

echo "# Couchbase Server Online"
echo "# Starting setup process"

echo "# Setting up memory"
curl -i "http://127.0.0.1:8091/pools/default" \
    -d memoryQuota=${MEMORY_QUOTA} \
    -d indexMemoryQuota=${INDEX_MEMORY_QUOTA} \
    -d ftsMemoryQuota=${FTS_MEMORY_QUOTA}

echo "# Setting up services"
curl -i "http://127.0.0.1:8091/node/controller/setupServices" \
    -d services="${SERVICES}"

echo "# Setting up user credentials"
curl -i "http://127.0.0.1:8091/settings/web" \
    -d port=8091 \
    -d username=${USERNAME} \
    -d password=${PASSWORD}

echo "# Setting up the bucket"
curl -i "http://127.0.0.1:8091/pools/default/buckets" \
    -d name=phpfastcache \
    -d ramQuotaMB=256 \
    -u ${USERNAME}:${PASSWORD} \


echo "# Couchbase running successfully" 
