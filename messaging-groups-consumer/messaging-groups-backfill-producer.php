<?php
/**
 * messaging-groups-backfill-producer.php
 *
 * A producer app to backfill users that we missed to Messaging Groups on Mobile Commons.
 */

use DoSomething\MessagingGroupsConsumer\MessagingGroupsBackfillProducer;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Manage enviroment setting
if (isset($_GET['environment']) && allowedEnviroment($_GET['environment'])) {
  define('ENVIRONMENT', $_GET['environment']);
} elseif (isset($argv[1])&& allowedEnviroment($argv[1])) {
  define('ENVIRONMENT', $argv[1]);
} elseif ($env = loadConfig() && defined('ENVIRONMENT')) {
  echo 'environment.php exists, ENVIRONMENT defined as: ' . ENVIRONMENT, PHP_EOL;
} elseif (allowedEnviroment('local')) {
  define('ENVIRONMENT', 'local');
}

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/messaging-groups-consumer.config.inc';

echo '------- messaging-groups-backfill-producer START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
// Kick off - blocking, waiting for messages in the queue
try {
  $backfillProducer = new MessagingGroupsBackfillProducer();
  $backfillProducer->processSignupCSV();
  $backfillProducer->processReportbackCSV();
} catch (Exception $e) {
  echo $e->getMessage() . PHP_EOL;
}
echo '------- messaging-groups-backfill-producer END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;


/**
 * Test if environment setting is a supported value.
 *
 * @param string $setting Requested enviroment setting.
 *
 * @return boolean
 */
function allowedEnviroment($setting)
{

  $allowedEnviroments = [
    'local',
    'dev',
    'thor',
    'prod',
  ];

  if (in_array($setting, $allowedEnviroments)) {
    return true;
  }

  return false;
}

/**
 * Gather configuration settings for current application environment.
 *
 * @return boolean
 */
function loadConfig() {

  // Check that environment config file exists
  if (!file_exists(__DIR__ . '/environment.php')) {
    return false;
  }
  include(__DIR__ . '/environment.php');

  return true;
}
