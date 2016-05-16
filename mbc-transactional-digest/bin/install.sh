#!/bin/bash
##
# Installation script for mbc-transactional-digest
##

# Assume messagebroker-config repository is one directory up
cd ../../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbc-transactional-digest
cd ../MessageBroker-PHP/mbc-transactional-digest

# Create SymLink for mbc-transactional-digest application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .
