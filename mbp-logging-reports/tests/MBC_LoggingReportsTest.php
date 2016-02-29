<?php
/**
 *
 */

namespace DoSomething\MBC_LoggingReports\Test;

/**
 * MBP_LoggingReportsTest: test coverage for MBP_LoggingReports class.
 */
class MBP_LoggingReportsTest extends PHPUnit_Framework_TestCase {
 
class MBC_LoggingProcessoryTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
  
  /**
   *
   */
  public function testDailyUserImport()
  {
 
    date_default_timezone_set('America/New_York');

    // Load Message Broker settings used by mbp-logging-reports.php
    require_once __DIR__ . '/../mbp-logging-reports.config.inc';

    // Assertion stub
    $this->assertTrue(true);

  }
}
