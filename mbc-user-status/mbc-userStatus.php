<?php
/**
 * mbc-userStatus.php
 *
 * Collect ?? from the ?? Queue. Process the entries to ??.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-userStatus.config.inc';

echo '------- mbc-user-status START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off - blocking, waiting for messages in the queue
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_UserStatus($mb, $settings), 'userStatus'));

echo '------- mbc-user-status END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
