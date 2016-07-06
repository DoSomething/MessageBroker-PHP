<?php
/**
 * mbc-transactional-digest
 *
 * Collect transactional campaign sign up message requests in a certain time period and
 * compose a single digest message request.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
define('QOS_SIZE', 1);

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\ MBC_TransactionalDigest\MBC_TransactionalDigest_Consumer;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-transactional-digest.config.inc';


// Kick off
echo '------- mbc-transactional-digest START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

$mb = $mbConfig->getProperty('messageBroker_transactionalDigest');
$mb->consumeMessage([new MBC_TransactionalDigest_Consumer('messageBroker_transactionalDigest'), 'consumeQueue'], QOS_SIZE);

echo '------- mbc-transactional-digest END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
