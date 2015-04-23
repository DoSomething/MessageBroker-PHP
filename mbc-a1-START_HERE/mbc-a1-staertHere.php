<?php
/**
 * mbc-a1_START_HERE.php
 *
 * Collect ?? from the ?? Queue. Process the entries to ??.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require __DIR__ . '/MBC_A1_StartHere.class.inc';

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
  'ds_user_api_host' => getenv('DS_USER_API_HOST'),
  'ds_user_api_port' => getenv('DS_USER_API_PORT'),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$transactionalExchange = $mb_config->exchangeSettings('transactionalExchange');

$config['exchange'] = array(
  'name' => $transactionalExchange->name,
  'type' => $transactionalExchange->type,
  'passive' => $transactionalExchange->passive,
  'durable' => $transactionalExchange->durable,
  'auto_delete' => $transactionalExchange->auto_delete,
);
foreach ($transactionalExchange->queues->someQueue->binding_patterns as $binding_key) {
  $config['queue'][] = array(
    'name' => $transactionalExchange->queues->someQueue->name,
    'passive' => $transactionalExchange->queues->someQueue->passive,
    'durable' =>  $transactionalExchange->queues->someQueue->durable,
    'exclusive' =>  $transactionalExchange->queues->someQueue->exclusive,
    'auto_delete' =>  $transactionalExchange->queues->someQueue->auto_delete,
    'bindingKey' => $binding_key,
  );
}


// Kick off - blocking, waiting for messages in the queue
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_A1_StartHere($mb, $settings), 'startHere'));
