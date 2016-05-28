<?php
/**
 * mbc-transactional-digest_shim
 *
 * Generate "shim" message for mbc-transactional-digest to consume. Shim messages will be
 * generated on a timed interval to ensure mbc-transactional-digest consumer is active
 * and dispatching digest messages in times when event message rates are slow.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\ MBC_TransactionalDigest\MBP_TransactionalDigest_Producer;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-transactional-digest_shim.config.inc';


// Kick off
echo '------- mbc-transactional-digest_shim START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

$mbpTransactionalDigest = new MBP_TransactionalDigest_Producer();
$mbpTransactionalDigest->produceShim();

echo '------- mbc-transactional-digest_shim END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
