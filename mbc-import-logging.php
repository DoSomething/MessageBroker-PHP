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
use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/mb-config.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'use_stathat_tracking' => getenv('USE_STAT_TRACKING'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$userImportExistingLogging = $mb_config->exchangeSettings('directUserImportExistingLogging');

$config['exchange'] = array(
  'name' => $userImportExistingLogging->name,
  'type' => $userImportExistingLogging->type,
  'passive' => $userImportExistingLogging->passive,
  'durable' => $userImportExistingLogging->durable,
  'auto_delete' => $userImportExistingLogging->auto_delete,
);
$config['queue'][] = array(
  'name' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->name,
  'passive' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->passive,
  'durable' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->durable,
  'exclusive' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->exclusive,
  'auto_delete' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->auto_delete,
  'bindingKey' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->binding_key,
);
$config['routing_key'] = $userImportExistingLogging->queues->userImportExistingLoggingQueue->routing_key;
$config['consume'] = array(
  'no_local' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->no_local,
  'no_ack' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->no_ack,
  'nowait' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->nowait,
  'exclusive' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->exclusive,
);


echo '------- mbc-impoert-logging START - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ImportLogging($mb, $settings), 'updateLoggingAPI'));


echo '------- mbc-impoert-logging END - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
