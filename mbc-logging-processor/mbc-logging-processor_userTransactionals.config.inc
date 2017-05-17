<?php
/**
 * Configuration settings for mbc-logging-processor_userTransactionals.
 *
 * Message Broker configuration settings for mbc-logging-processor_userTransactionals
 * application. The MB_Configuration class is used to create a singleton instance that
 * can be referenced anywhere in the application for application configuration settings.
 *
 * @package mbc-logging-processor
 * @link    https://github.com/DoSomething/MessageBroker-PHP/tree/master/mbc-logging-processorl
 */

/**
 * Load configuration settings into singleton instance with MB_Configuration class.
 */
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\StatHat\Client as StatHat;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once __DIR__ . '/messagebroker-config/mb-secure-config.inc';

$mbConfig = MB_Configuration::getInstance();

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
$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');

// Create connection to exchange and queue for gathering of transactional logging
// queue contents.
$messageBrokerConfig = $mbConfig->constructRabbitConfig('transactionalExchange', array('loggingQueue'));
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

// Create connection to exchange and queue for sending of transactional logging
// messages.
$messageBrokerConfig = $mbConfig->constructRabbitConfig('directLoggingGateway', array('loggingGatewayQueue'));
$mbConfig->setProperty('messageBroker_LoggingGateway', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

// RabbitMQ Management API connection to maintain queue status.
$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
  'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
  'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
  'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
  'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));

