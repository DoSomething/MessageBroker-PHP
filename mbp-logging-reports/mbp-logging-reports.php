<?php
/**
 * mbp-user-digest.php
 *
 * A producer to create entries in the userDigestQueue via the directUserDigest
 * exchange. The mbc-user-digest application will consume the entries in the
 * queue.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

require __DIR__ . '/MBP_UserImport_Report.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);
$config = array(
  'exchange' => array(
    'name' => getenv("MB_TRANSACTIONAL_EXCHANGE"),
    'type' => getenv("MB_TRANSACTIONAL_EXCHANGE_TYPE"),
    'passive' => getenv("MB_TRANSACTIONAL_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_TRANSACTIONAL_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_TRANSACTIONAL_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    'transactional' => array(
      'name' => getenv("MB_TRANSACTIONAL_QUEUE"),
      'passive' => getenv("MB_TRANSACTIONAL_QUEUE_PASSIVE"),
      'durable' => getenv("MB_TRANSACTIONAL_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_TRANSACTIONAL_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_TRANSACTIONAL_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_TRANSACTIONAL_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
  'routingKey' => getenv("MB_IMPORT_REPORT_ROUTING_KEY"),
);
$settings = array(
  'mailchimp_apikey' => getenv("MAILCHIMP_APIKEY"),
  'mailchimp_list_id' => getenv("MAILCHIMP_LIST_ID"),
  'mobile_commons_username' => getenv("MOBILE_COMMONS_USER"),
  'mobile_commons_password' => getenv("MOBILE_COMMONS_PASSWORD"),
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
  'ds_drupal_api_host' => getenv('DS_DRUPAL_API_HOST'),
  'ds_drupal_api_port' => getenv('DS_DRUPAL_API_PORT'),
);

if (isset($_GET['targetDuration'])) {
  $targetDuration = $_GET['targetDuration'];
}
elseif (isset($argv[1])) {
  $targetDuration = $argv[1];
}
else {
  $targetDuration = 'day';
}

// Collect targetDate parameter
if (isset($_GET['targetDate'])) {
  $targetDate = $_GET['targetFile'];
}
elseif (isset($argv[2])) {
  $targetDate = $argv[2];
}
else {
  $targetDate = date('Y-m-d');
}

if (isset($_GET['source'])) {
  $source = $_GET['source'];
}
elseif (isset($argv[3])) {
  $source = $argv[3];
}
else {
  $source = 'all';
}

echo '------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
$mbpLoggingReport = new  MBP_UserImport_Report($credentials, $config, $settings);

// Gather digest message mailing list
$mbpLoggingReport->generateReports($targetDuration, $targetDate, $source);

echo '------- mbp-logging-reports END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
