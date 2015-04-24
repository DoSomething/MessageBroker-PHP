mbc-image-processor
=================

Message Broker - Consumer

A consumer to process the ?? queue. Messages with the "report_back" activity will have a http request sent to the image path to trigger the generation of the various image sizes defined in the related image style within the Drupal web site. Doing the image processing asynchronously will result in a "smoother" and potentially faster experience for the users.

\Basic Message Broker consumer setup
------------------------------------
- from a clone of the messagebroker-ds-PHP repository on the target server configured for PHP applications
- create the messagebroker-config symbolic link in the root of the application
  $ ln -s ../../messagebroker-config .
- create the daemon configuration file
- start the daemon process
  $ sudo start mbc-image-processor
