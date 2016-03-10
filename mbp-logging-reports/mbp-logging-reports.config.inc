<?php
/**
 * Message Broker configuration settings for mbc-logging-processor
 */

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_Slack;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

define("ALLOWED_SOURCES", serialize([
  'all',
  'niche',
  'afterschool'
]));

$mbConfig = MB_Configuration::getInstance();

// Settings
$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv("DISABLE_STAT_TRACKING")
]));
$mbConfig->setProperty('mb_logging_api_config', [
  'host' => getenv("MB_LOGGING_API_HOST"),
  'port' => getenv('MB_LOGGING_API_PORT')
]);
$mbConfig->setProperty('ds_drupal_api_config', [
  'host' => getenv("DS_DRUPAL_API_HOST"),
  'port' => getenv('DS_DRUPAL_API_PORT'),
  'username' => getenv("DS_DRUPAL_API_USERNAME"),
  'password' => getenv('DS_DRUPAL_API_PASSWORD'),
]);
$mbConfig->setProperty('slack_config', [
  'webhookURL_message-broker' => getenv("SLACK_WEBHOOK_MESSAGEBROKER"),
  'webhookURL_after-school-internal' => getenv("SLACK_WEBHOOK_AFTERSCHOOL_INTERNAL")
]);
$mbConfig->setProperty('rabbit_credentials', [
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
]);

$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$mbRabbitConfig = $mbConfig->constructRabbitConfig('transactionalExchange', ['transactionalQueue']);
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $mbRabbitConfig));

$mbConfig->setProperty('mbToolbox', new MB_Toolbox());
$mbConfig->setProperty('mbToolboxcURL', new MB_Toolbox_cURL());
$mbConfig->setProperty('slack', new MB_Slack());
