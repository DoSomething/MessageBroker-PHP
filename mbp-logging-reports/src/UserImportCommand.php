<?php
/**
 *
 */

namespace DoSomething\MBP_LoggingReports;

use Symfony\Component\Console\Command\Command;


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
  public function execute()
  {

  }
}