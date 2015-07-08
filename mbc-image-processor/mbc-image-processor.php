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
use DoSomething\MBC_ImageProcessor\MBC_ImageProcessingConsumer;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-image-processor.config.inc';

// Create objects for injection into MBC_ImageProcessor
$mb = new MessageBroker($credentials, $config);
$sh = new StatHat([
  'ez_key' => $settings['stathat_ez_key'],
  'debug' => $settings['stathat_disable_tracking']
]);
$tb = new MB_Toolbox($settings);

// Kick off - block, waiting for messages in queue
echo '------- mbc-logging-gateway START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
$mb->consumeMessage(array(new  MBC_ImageProcessingConsumer($mb, $sh, $tb, $settings), 'consumeImageProcessingQueue'));
echo '------- mbc-logging-gateway END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
