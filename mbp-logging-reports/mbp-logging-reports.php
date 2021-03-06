<?php
/**
 * mbp-logging-reports.php
 *
 * A producer to create reports based on logged data in mb-logging-api.
 */

use DoSomething\MBP_LoggingReports\MBP_LoggingReports_Users;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mbp-logging-reports.config.inc';

if (isset($_GET['source'])) {
  $sources[0] = $_GET['source'];
}
elseif (isset($argv[1])) {
  $sources[0] = $argv[1];
}
if ($sources[0] == 'all') {
  $sources = [
    'niche',
    'afterschool'
  ];
}

if (isset($_GET['startDate'])) {
  $startDate = $_GET['startDate'];
}
elseif (isset($argv[2])) {
  $startDate = $argv[2];
}
else {
  $startDate = '01-' . date('m-Y');
}

echo '------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

try {

  // Kick off
  $mbpLoggingReport = new MBP_LoggingReports_Users();

  // Gather digest message mailing list
  $mbpLoggingReport->report('runningMonth', $sources, $startDate);
}
catch(Exception $e) {
  echo $e->getMessage(), PHP_EOL;
}

echo '------- mbp-logging-reports END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
