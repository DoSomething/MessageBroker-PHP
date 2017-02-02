<?php
/**
 * Message Broker configuration settings for mbc-a1startHere
 */
use DoSomething\MessageBroker\MessageBroker;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_MobileCommons;
use DoSomething\MB_Toolbox\MB_RabbitMQManagementAPI;
use DoSomething\Gateway\Northstar;

use DoSomething\DeadLetter\DeadLetterFilter_Northstar_OAuthBridge;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require_once CONFIG_PATH . '/mb-secure-config/' . ENVIRONMENT . '/mb-secure-config.inc';

$mbConfig = MB_Configuration::getInstance();

// Settings
$mbConfig->setProperty('generalSettings', []);
$mbConfig->setProperty('rabbit_credentials', [
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
]);
$mbConfig->setProperty('rabbitapi_credentials', [
  'host' =>  getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv("MB_RABBITMQ_MANAGEMENT_API_PORT"),
  'username' => getenv("MB_RABBITMQ_MANAGEMENT_API_USERNAME"),
  'password' => getenv("MB_RABBITMQ_MANAGEMENT_API_PASSWORD"),
]);
$mbRabbitConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', array('deadLetterQueue'));
$mbConfig->setProperty('rabbit_config', $mbRabbitConfig);

// Create untility objects for access anywhere within the application as a single instance
$messageBrokerConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$rabbitCredentials = $mbConfig->getProperty('rabbit_credentials');
$mbConfig->setProperty('messageBroker', new MessageBroker($rabbitCredentials, $messageBrokerConfig));

$messageBrokerConfig = $mbConfig->constructRabbitConfig('directDeadLetterExchange', ['deadLetterQueue']);
$mbConfig->setProperty('messageBroker_deadLetter', new MessageBroker($rabbitCredentials, $messageBrokerConfig));


$mbConfig->setProperty('statHat', new StatHat([
  'ez_key' => getenv("STATHAT_EZKEY"),
  'debug' => getenv('DISABLE_STAT_TRACKING')
]));

$mbConfig->setProperty('mbToolbox', new MB_Toolbox());

// Rabbit Management.
$mbConfig->setProperty('mbRabbitMQManagementAPI', new MB_RabbitMQManagementAPI([
  'domain' => getenv("MB_RABBITMQ_MANAGEMENT_API_HOST"),
  'port' => getenv('MB_RABBITMQ_MANAGEMENT_API_PORT'),
  'vhost' => getenv('MB_RABBITMQ_MANAGEMENT_API_VHOST'),
  'username' => getenv('MB_RABBITMQ_MANAGEMENT_API_USERNAME'),
  'password' => getenv('MB_RABBITMQ_MANAGEMENT_API_PASSWORD')
]));

// Northstar
$northstar = new Northstar([
    'grant' => 'client_credentials', // Default OAuth grant to use: either 'authorization_code' or 'client_credentials'
    'url' => getenv("NORTHSTAR_API_HOST"), // the environment you want to connect to
    'bridge' => DeadLetterFilter_Northstar_OAuthBridge::class, // class which handles saving/retrieving tokens

    // Then, configure client ID, client secret, and scopes per grant.
    'client_credentials' => [
        'client_id' => getenv("NORTHSTAR_API_ID"),
        'client_secret' => getenv('NORTHSTAR_API_KEY'),
        'scope' => ['user', 'admin'],
    ],
]);
$mbConfig->setProperty('northstar', $northstar);
