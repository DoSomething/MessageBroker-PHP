#!/bin/bash
##
# Installation script for mbp-logging-reports
##

# Assume messagebroker-config repo is one directory up
cd ../../messagebroker-config

# Gather path from root
MBCONFIG=`pwd`

# Back to mbp-logging-reports
cd ../MessageBroker-PHP/mbp-logging-reports

# Create SymLink for mbp-logging-reports application to make reference to for all Message Broker configuration settings
ln -s $MBCONFIG .
