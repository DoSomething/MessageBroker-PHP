<?php
/**
 * mbc-logging-gateway
 *
 * Collect user import activity from the userImportExistingLoggingQueue. Update
 * the LoggingAPI / database with import activity via mb-logging.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on the same queue.
define('QOS_SIZE', 1);

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_LoggingGateway\MBC_LoggingGateway;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-logging-gateway.config.inc';


// Kick off
echo '------- mbc-impoert-logging START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;


$mb = $mbConfig->getProperty('messageBroker');
$mb->consumeMessage(array(new MBC_LoggingGateway(), 'consumeLoggingGatewayQueue'), QOS_SIZE);

echo '------- mbc-impoert-logging END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
