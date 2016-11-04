<?php
/**
 * messaging-groups-consumer.php
 *
 * A consumer app to manage Messaging Groups on Mobile Commons.
 */

use DoSomething\MessagingGroupsConsumer\MessagingGroupsConsumer;

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/messaging-groups-consumer.config.inc';

echo '------- messaging-groups-consumer START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

// Kick off - blocking, waiting for messages in the queue
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MessagingGroupsConsumer($mb, $settings), 'consume'));

echo '------- messaging-groups-consumer END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
