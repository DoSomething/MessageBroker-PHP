<?php
/**
 * dead-letter-filter.php
 *
 * A consumer app to filter deadLetter queue messages.
 */

use DoSomething\DeadLetter\DeadLetterFilter;

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
require_once __DIR__ . '/dead-letter-filter.config.inc';

// ---  Options ---
$opts = CLIOpts\CLIOpts::run("
{self}
--dry-run Test run, doesn't actually changes data
-h, --help Show this help
");

$args = (array) $opts;

define("DRY_RUN", isset($args['dry-run']));

echo '------- dead-letter-filter START: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
if (DRY_RUN) {
  echo '@@@@@@@@ DRY RUN MODE ON @@@@@@@@' . PHP_EOL;
}
// Kick off - blocking, waiting for messages in the queue
$mb = $mbConfig->getProperty('messageBroker');

$consumer = new DeadLetterFilter('messageBroker', $args);
$mb->getAllMessages(array($consumer, 'filterDeadLetterQueue'));
echo '------- dead-letter-filter END: ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;


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
