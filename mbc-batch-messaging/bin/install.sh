#!/bin/bash
##
# Installation script for mbc-batch-messaging
##

# Assume messagebroker-config repo is one directory up
cd ../../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbc-batch-messaging
cd ../MessageBroker-PHP/mbc-batch-messaging

# Create SymLink for mbc-batch-messaging application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .
