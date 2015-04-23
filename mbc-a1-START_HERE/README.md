mbc-a1-START_HERE
=================

Message Broker - Consumer

A collection of "starter" / template files to copy when creating a new consumer micro-service application for the DoSomething.org Message Broker system.

Setup Steps
----------
1. Copy and rename the mbc-a1-START_HER directory and files to the new project (consumer PHP application).
2. Adjust the composer.json file to reflect the new project. The default require projects are typical of all consumers in the Message Broker PHP applications. 
3. Rename mbc-a1-staertHere.php to a name relivent to the application name (folder, base application name and base class file).
3.1 Adjust exchange and queue connection settings within mbc-a1-staertHere.php based on what the consumer application needs to process.
3.2 Settings in the messagebroker-config repo host the exchange and queue settings shared between all of the Message Broker appllications.
3.3 Create a sym link to the messagebroker-config repo to allow access to the config settings:
    $ ln -s ../../messagebroker-config .

