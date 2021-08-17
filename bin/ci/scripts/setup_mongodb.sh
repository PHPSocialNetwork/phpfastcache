#!/bin/bash

mongo pfc_test --eval 'db.createUser({user:"travis",pwd:"test",roles:["readWrite"]});'
