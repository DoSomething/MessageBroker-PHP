<?php
/**
 * mbc-logging-processor_userTransactionals
 *
 * Collect user transactions from loggingQueue to send to loggingGatewayQueue the
 * for logging.
 */

 $bla = FALSE;
if ($bla) {
  $bla = TRUE;
}

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on
// the same queue.
define('QOS_SIZE', 1);

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_LoggingProcessor\MBC_LoggingProcessor_UserTransactions_Consumer;

require_once __DIR__ . '/mbc-logging-processor_userTransactionals.config.inc';

// Kick off
echo '------- mbc-logging-processor_userTransactions START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

$mb = $mbConfig->getProperty('messageBroker');
$mb->consumeMessage(array(new MBC_LoggingProcessor_UserTransactions_Consumer(), 'consumeLoggingQueue'), QOS_SIZE);

echo '-------mbc-logging-processor_userTransactions END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
