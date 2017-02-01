#!/bin/bash

# Assume messagebroker-config repo is one directory up
cd ../../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbp-user-import
cd ../MessageBroker-PHP/dead-letter-filter

# Create SymLink for application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .
