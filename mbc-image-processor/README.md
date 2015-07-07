mbc-image-processor
=================

Message Broker - Consumer

A consumer to process the imageProcessingQueue queue. Messages with the "report_back" activity will trigger a http request to the image path. The request will to trigger the generation of the various image sizes defined in the related image style within the Drupal web site. Doing the image processing asynchronously will result in a "smoother" and potentially faster experience for the users.

Basic Message Broker consumer setup
------------------------------------
- from a clone of the messagebroker-ds-PHP repository on the target server configured for PHP applications
- Install the application using Composer
  $ composer install
  - A symbolic link to messagebroker-config will be created at the root of the site.
- create the daemon configuration file at `$/etc/init/mbc-image-processor.conf` based on other "mbc" conf files in the directory
  - edit the file to make it mbc-image-processor specific.
- start the daemon process
  $ sudo start mbc-image-processor

Contents of imageProcessingQueue will be processed immediately. The queue should be at or quickly become zero messages at all times.