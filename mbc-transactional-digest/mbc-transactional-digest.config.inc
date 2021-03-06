<?php
/**
 * Message Broker configuration settings for mbc-transactional-digest
 */


use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\StatHat\Client as StatHat;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

$mbConfig = MB_Configuration::getInstance();

// Settings
$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv("DISABLE_STAT_TRACKING")
]));
$mbConfig->setProperty('rabbit_credentials', [
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
]);
$mbConfig->setProperty('ds_drupal_api_config', [
  'host' => getenv("DS_DRUPAL_API_HOST"),
  'port' => getenv('DS_DRUPAL_API_PORT'),
  'username' => getenv("DS_DRUPAL_API_USERNAME"),
  'password' => getenv('DS_DRUPAL_API_PASSWORD'),
]);
$mbConfig->setProperty('mb_logging_api_config', [
  'host' => getenv("MB_LOGGING_API_HOST"),
  'port' => getenv('MB_LOGGING_API_PORT')
]);

$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', ['transactionalDigestQueue']);
$mbConfig->setProperty('messageBroker_transactionalDigest', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', ['transactionalQueue']);
$mbConfig->setProperty('transactionalEmailQueue', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', ['mobileCommonsQueue']);
$mbConfig->setProperty('transactionalSMSQueue', new MessageBroker($rabbitCredentials, $mbRabbitConfig));
$mbRabbitConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$mbConfig->setProperty('messageBroker_deadLetter', new MessageBroker($rabbitCredentials, $mbRabbitConfig));

$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
  'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
  'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
  'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
  'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));

$mbConfig->setProperty('mbToolbox', new MB_Toolbox());
$mbConfig->setProperty('mbToolboxcURL', new MB_Toolbox_cURL());
