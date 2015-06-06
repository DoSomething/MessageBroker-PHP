<?php
/**
 * Message Broker configuration settings for mbc-import-logging
 */


use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

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
  'stathat_disable_tracking' => getenv('DISABLE_STAT_TRACKING'),
  'mb_logging_api_host' => getenv('MB_LOGGING_API_HOST'),
  'mb_logging_api_port' => getenv('MB_LOGGING_API_PORT'),
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