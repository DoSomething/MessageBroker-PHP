<?php
/**
 * mbc-import-logging.php
 *
 * Collect user import activity from the userImportExistingLoggingQueue. Update
 * the LoggingAPI / database with import activity via mb-logging.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_ImportLogging\MBC_ImportLogging;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-import-logging.config.inc';


echo '------- mbc-impoert-logging START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ImportLogging($mb, $settings), 'updateLoggingAPI'));

echo '------- mbc-impoert-logging END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
