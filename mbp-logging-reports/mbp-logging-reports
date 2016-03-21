#! /usr/bin/env php
<?php
/**
 * mbp-logging-reports.php
 *
 * A producer to create reports based on logged data in mb-logging-api.
 */

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DoSomething\MBP_LoggingReports\MBP_LoggingReports_Users;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mbp-logging-reports.config.inc';

$app = new Application();

$app->register('mbp-logging-reports')
  ->addArgument('source')
  ->setCode(function(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------');
  });

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

echo '------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

try {

  // Kick off
  $mbpLoggingReport = new MBP_LoggingReports_Users();

  // Gather digest message mailing list
  $mbpLoggingReport->report('runningMonth', $sources);
}
catch(Exception $e) {
  echo $e->getMessage(), PHP_EOL;
}

echo '------- mbp-logging-reports END: ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
