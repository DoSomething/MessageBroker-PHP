<?php
/**
 * mbp-logging-reports.php
 *
 * A producer to create reports based on logged data in mb-logging-api.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBP_LoggingReports\MBP_LoggingReports_Users;

if (isset($_GET['source'])) {
  $source = $_GET['source'];
}
elseif (isset($argv[3])) {
  $source = $argv[3];
}
else {
  $source = 'all';
}

echo '------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mbpLoggingReport = new MBP_LoggingReports_Users();

// Gather digest message mailing list
$mbpLoggingReport->report('nicheRunningMonth');

echo '------- mbp-logging-reports END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
