<?php
/**
 *
 */

namespace DoSomething\MBP_LoggingReports;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use DoSomething\MBP_LoggingReports\MBP_LoggingReports_Users;

/**
 * Class UserImportCommand
 * @package DoSomething\MBP_LoggingReports
 */
class UserImportCommand extends Command
{

  /**
   * configure() - define values related to the command
   */
  public function configure()
  {
    require_once __DIR__ . '/../mbp-logging-reports.config.inc';

    $this->setName('user-import')
      ->setDescription('Report generation for user import activity.')
      ->addArgument('source', InputArgument::REQUIRED, 'Import source: all, niche, afterschool');
  }

  /**
   * execute() - The actions to take when the the command is executed.
   *
   * @param InputInterface $input
   *   Parameters defined when the command was executed.
   * @param OutputInterface $output
   *   Values to be sent to the outstream for display to the operator.
   *
   * @return int|null|void
   */
  public function execute(InputInterface $input, OutputInterface $output)
  {
    $sources[0] = $input->getArgument('source');

    // @todo: Move to class to support single report that lists all sources
    if ($sources[0] == 'all' || $sources[0] == null) {
      $sources = [
        'niche',
        'afterschool'
      ];
    }

    $output->writeln('------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------');

    try {

      $mbpLoggingReport = new MBP_LoggingReports_Users();
      //  $mbpLoggingReport->report('runningMonth', $sources);
    }
    catch(Exception $e) {
      $output->writeln($e->getMessage());
    }

    $output->writeln('------- mbp-logging-reports END: ' . date('D M j G:i:s T Y') . ' -------');
  }
}