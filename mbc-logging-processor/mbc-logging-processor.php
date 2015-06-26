<?php
/**
 * mbc-import-logging.php
 *
 * Collect user import activity from the userImportExistingLoggingQueue. Update
 * the LoggingAPI / database with import activity via mb-logging.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_LoggingGateway\MBC_LoggingGateway;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-logging-processor.config.inc';


echo '------- mbc-logging-processor START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
if (isset($_GET['interval'])) {
  $interval = $_GET['interval'];
}
elseif (isset($argv[1])) {
  $interval = $argv[1];
}
if (isset($_GET['offset'])) {
  $offset = $_GET['offset'];
}
elseif (isset($argv[2])) {
  $offset = $argv[2];
}

// Validate
if (is_numeric($interval) && is_numeric($offset)) {

  $mbcLoggingProcessor = new MBC_LoggingProcessor($credentials, $config, $settings);
  $mbcLoggingProcessor->processLoggedEvents($interval, $offset);

}

echo '------- mbc-logging-processor END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
