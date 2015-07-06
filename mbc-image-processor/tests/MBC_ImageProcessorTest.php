<?php

use DoSomething\MBC_LoggingGateway\MBC_LoggingGateway;

  // Including that file will also return the autoloader instance, so you can store
  // the return value of the include call in a variable and add more namespaces.
  // This can be useful for autoloading classes in a test suite, for example.
  // https://getcomposer.org/doc/01-basic-usage.md
  $loader = require_once __DIR__ . '/../vendor/autoload.php';
 
class  MBC_ImageProcessorTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testProcessImage()
  {

    date_default_timezone_set('America/New_York');

    // Load Message Broker settings used mb mbp-user-import.php
    define('CONFIG_PATH',  __DIR__ . '/../messagebroker-config');
    require_once __DIR__ . '/../mbc-image-processor.config.inc';

    // Create  MBP_ImageProcessor object to access ?? method for testing
    $messageBroker = new MessageBroker($credentials, $config);
    $mbcLoggingGateway = new MBC_ImageProcessor($messageBroker, $settings);

    
    $this->assertTrue(TRUE);
  }
 
}
