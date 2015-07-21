<?php
/**
 * mbc-user-digest.php
 *
 * A consumer to process entries in the registrationMobileQueue via the
 * transactionalExchange. The mbp-registration-mobile application produces user
 * entries in Mobile Commons based the contents of the queue.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/MBC_RegistrationMobile.class.inc';

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
foreach ($transactionalExchange->queues->mobileCommonsQueue->binding_patterns as $bindingCount => $bindingKey) {
  $config['queue'][$bindingCount] = array(
    'name' => $transactionalExchange->queues->mobileCommonsQueue->name,
    'passive' => $transactionalExchange->queues->mobileCommonsQueue->passive,
    'durable' =>  $transactionalExchange->queues->mobileCommonsQueue->durable,
    'exclusive' =>  $transactionalExchange->queues->mobileCommonsQueue->exclusive,
    'auto_delete' =>  $transactionalExchange->queues->mobileCommonsQueue->auto_delete,
    'bindingKey' => $bindingKey,
  );
}
$config['consume'] = array(
  'no_local' => $transactionalExchange->queues->mobileCommonsQueue->consume->no_local,
  'no_ack' => $transactionalExchange->queues->mobileCommonsQueue->consume->no_ack,
  'nowait' => $transactionalExchange->queues->mobileCommonsQueue->consume->nowait,
  'exclusive' => $transactionalExchange->queues->mobileCommonsQueue->consume->exclusive,
);


echo '------- mbc-registration-mobile START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_RegistrationMobile($mb, $settings), 'consumeRegistrationMobileQueue'));

echo '------- mbc-registration-mobile END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
