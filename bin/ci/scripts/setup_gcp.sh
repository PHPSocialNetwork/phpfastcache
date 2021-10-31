#!/bin/bash
if [ -z ${BASE64_GOOGLE_APPLICATION_CREDENTIALS+x} ] || [ -z ${GOOGLE_APPLICATION_CREDENTIALS+x} ];
then
    echo "GCP secret variables are not set, ignoring..."
else
    printenv BASE64_GOOGLE_APPLICATION_CREDENTIALS | base64 --decode > "${GOOGLE_APPLICATION_CREDENTIALS}"
fi
