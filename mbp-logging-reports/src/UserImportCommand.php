<?php
/**
 *
 */

namespace DoSomething\MBP_LoggingReports;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;


class UserImportCommand extends Command
{

  /**
   *
   */
  public function configure()
  {
    $this->setName('user-import')
      ->setDescription('Report generation for user import activity.')
      ->addArgument('source', InputArgument::REQUIRED, 'Import source: all, niche, afterschool');
  }

  /**
   *
   */
  public function execute(InputInterface $input, OutputInterface $output)
  {
    $source = $input->getArgument('source');
    $output->writeln('------- mbp-logging-reports START: ' . date('D M j G:i:s T Y') . ' -------');
  }
}