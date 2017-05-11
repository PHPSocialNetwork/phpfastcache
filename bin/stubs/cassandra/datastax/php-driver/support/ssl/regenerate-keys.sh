#!/bin/bash

set -ex

basedir=$(dirname $0)

server_cert="$basedir/cassandra.pem"
client_cert="$basedir/driver.pem"
private_key="$basedir/driver.key"
passphrase="php-driver"

skeystore="$basedir/.keystore"
skstorepass="php-driver"
struststore="$basedir/.truststore"
ststorepass="php-driver"
ckeystore="$TMPDIR/driver.keystore"
ckstorepass="php-driver"
ckeystorep12="$TMPDIR/driver-keystore.p12"

rm -f "$server_cert" "$client_cert" "$private_key" "$skeystore" \
  "$struststore" "$ckeystore" "$ckeystorep12"

keytool -genkeypair -noprompt \
  -keyalg RSA \
  -validity 36500 \
  -alias cassandra \
  -keystore "$skeystore" \
  -storepass "$skstorepass" \
  -dname "CN=Cassandra Server, OU=PHP Driver Tests, O=DataStax Inc., L=Santa Clara, ST=California, C=US"

keytool -exportcert -noprompt \
  -rfc \
  -alias cassandra \
  -keystore "$skeystore" \
  -storepass "$skstorepass" \
  -file "$server_cert"

chmod 400 "$server_cert"

keytool -genkeypair -noprompt \
  -keyalg RSA \
  -validity 36500 \
  -alias driver \
  -keystore "$ckeystore" \
  -storepass "$ckstorepass" \
  -dname "CN=PHP Driver, OU=PHP Driver Tests, O=DataStax Inc., L=Santa Clara, ST=California, C=US"

keytool -exportcert -noprompt \
  -alias driver \
  -keystore "$ckeystore" \
  -storepass "$ckstorepass" \
  -file driver.crt

keytool -import -noprompt \
  -alias cassandra \
  -keystore "$struststore" \
  -storepass "$ststorepass" \
  -file driver.crt

keytool -exportcert -noprompt \
  -rfc \
  -alias driver \
  -keystore "$ckeystore" \
  -storepass "$ckstorepass" \
  -file "$client_cert"

chmod 400 "$client_cert"

keytool -importkeystore -noprompt \
  -srcalias certificatekey \
  -deststoretype PKCS12 \
  -srcalias driver \
  -srckeystore "$ckeystore" \
  -srcstorepass "$ckstorepass" \
  -storepass "$ckstorepass" \
  -destkeystore "$ckeystorep12"

openssl pkcs12 -nomacver -nocerts \
  -in "$ckeystorep12" \
  -password pass:"$ckstorepass" \
  -passout pass:"$passphrase" \
  -out "$private_key"

chmod 400 "$private_key"
