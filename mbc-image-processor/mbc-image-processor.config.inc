<?php
/**
 * Message Broker configuration settings for mbc-import-logging
 */


use DoSomething\MB_Toolbox\MB_Configuration;

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
  'mb_user_api_host' => getenv('MB_USER_API_HOST'),
  'mb_user_api_port' => getenv('MB_USER_API_PORT'),
);

$mbConfig = new MB_Configuration($settings, CONFIG_PATH . '/mb_config.json');
$config = $mbConfig->constructConfig('transactionalExchange', array('imageProcessingQueue'));
