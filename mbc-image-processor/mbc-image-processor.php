<?php
/**
 * mbc-image-processor
 *
 * Collect image details from the imageProcessingQueue. Use image path to make http request
 * to trigger generation of image styles within Drupal application.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_ImageProcessor\MBC_ImageProcessor;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-image-processor.config.inc';


// Kick off - blocking, waiting for messages in the queue
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ImageProcessor($mb, $settings), 'consumeImageProcessingQueue'));
