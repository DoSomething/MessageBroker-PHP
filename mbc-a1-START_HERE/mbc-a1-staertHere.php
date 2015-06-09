<?php
/**
 * mbc-a1_START_HERE.php
 *
 * Collect ?? from the ?? Queue. Process the entries to ??.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-a1-staertHere.config.inc';

echo '------- mbc-a1-startHere START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off - blocking, waiting for messages in the queue
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_A1_StartHere($mb, $settings), 'startHere'));

echo '------- mbc-a1-startHere END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;