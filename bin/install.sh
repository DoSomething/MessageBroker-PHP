#!/bin/bash
##
# Installation script for mbp-user-import
##

# Assume messagebroker-config repo is one directory up
cd ../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbp-user-import
cd ../mbp-user-import

# Create SymLink for mbp-user-import application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .

