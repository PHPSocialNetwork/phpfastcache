#!/bin/bash

echo "# Setting up Mongodb database and user"
mongo pfc_test --eval 'db.createUser({user:"travis",pwd:"test",roles:["readWrite"]});'
