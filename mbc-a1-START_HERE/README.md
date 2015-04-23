mbc-a1-START_HERE
=================

Message Broker - Consumer

A collection of "starter" / template files to copy when creating a new consumer micro-service application for the DoSomething.org Message Broker system.

Setup Steps
----------
1. Copy and rename the mbc-a1-START_HER directory and files to the new project (consumer PHP application).
2. Adjust the composer.json file to reflect the new project. The default require projects are typical of all consumers in the Message Broker PHP applications. 
3. Rename mbc-a1-staertHere.php to a name relevant to the application name (folder, base application name and base class file).
3.1 Adjust exchange and queue connection settings within mbc-a1-staertHere.php based on what the consumer application needs to process.
3.2 Settings in the messagebroker-config repository host the exchange and queue settings shared between all of the Message Broker applications.
3.3 Create a symbolic link to the messagebroker-config repository to allow access to the config settings:
    $ ln -s ../../messagebroker-config/ .
4. Rename MBC_A1_StartHere.class.inc to match application name.
5. Adjust MBC_A1_StartHere.class to process message contents
5.1 Adjust __construct() StatHat setting to log activity specific to the new consumer
5.2 Create StatHat collection points through out the consumer to monitor on going application activity
5.2 Use MB_Toolbox functionality. There are several classes with methods common to all of the producers and consumers within the Message Broker PHP applications: https://github.com/DoSomething/mb-toolbox/tree/master/src
6. Testing
6.1 ...
7. Deployment to Production
7.1 ...
