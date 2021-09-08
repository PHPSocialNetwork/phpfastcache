#!/bin/bash

set -e

mkdir -p arangodb
cd arangodb
export ARANGODB_ROOT_PASSWD=password

curl -OL https://download.arangodb.com/arangodb38/DEBIAN/Release.key
sudo apt-key add - < Release.key

echo 'deb https://download.arangodb.com/arangodb38/DEBIAN/ /' | sudo tee /etc/apt/sources.list.d/arangodb.list
sudo apt-get install apt-transport-https
sudo apt-get update || true

sudo sh -c 'echo arangodb3 arangodb3/password password $ARANGODB_ROOT_PASSWD | debconf-set-selections'
sudo sh -c 'echo arangodb3 arangodb3/password_again password $ARANGODB_ROOT_PASSWD | debconf-set-selections'

sudo apt-get install arangodb3=3.8.1-1

printf "\n\n"
echo "#################################"
echo "# Now configuring the server..."
echo "#################################"
printf "\n"

echo "1/3 - Creating 'phpfastcache' user:"
curl -X POST -u root:$ARANGODB_ROOT_PASSWD --header 'accept: application/json' --data-binary @- --dump - http://localhost:8529/_api/user <<EOF
{
  "user" : "phpfastcache",
  "passwd" : "travis"
}
EOF
printf "\n\n"

echo "2/3 - Creating 'phpfastcache' database:"
curl -X POST -u root:$ARANGODB_ROOT_PASSWD --header 'accept: application/json' --data-binary @- --dump - http://localhost:8529/_api/database <<EOF
{
  "name" : "phpfastcache",
  "options" : {
    "writeConcern" : null,
    "replicationFactor" : null
  }
}
EOF
printf "\n\n"

echo "3/3 - Giving 'phpfastcache' user access to 'phpfastcache' database:"
curl -X PUT -u root:$ARANGODB_ROOT_PASSWD --header 'accept: application/json' --data-binary @- --dump - http://localhost:8529/_api/user/phpfastcache/database/phpfastcache <<EOF
{
  "grant" : "rw"
}
EOF
printf "\n\n"
