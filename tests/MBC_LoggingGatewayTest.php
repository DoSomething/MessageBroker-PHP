<?php

use DoSomething\MBC_LoggingGateway\MBC_LoggingGateway;

  // Including that file will also return the autoloader instance, so you can store
  // the return value of the include call in a variable and add more namespaces.
  // This can be useful for autoloading classes in a test suite, for example.
  // https://getcomposer.org/doc/01-basic-usage.md
  $loader = require_once __DIR__ . '/../vendor/autoload.php';
 
class  MBC_LoggingGatewayTest extends PHPUnit_Framework_TestCase {
  
  public function setUp(){ }
  public function tearDown(){ }
 
  public function testLogUserImportFile()
  {

    date_default_timezone_set('America/New_York');

    // Load Message Broker settings used mb mbp-user-import.php
    define('CONFIG_PATH',  __DIR__ . '/../messagebroker-config');
    require_once __DIR__ . '/../mbc-logging-gateway.config.inc';

    // Create  MBP_UserImport object to access findNextTargetFile() method for testing
    $messageBroker = new MessageBroker($credentials, $config);
    $mbcLoggingGateway = new MBC_LoggingGateway($messageBroker, $settings);
    
    list($endpoint, $cURLparameters, $post) = $mbcLoggingGateway->logUserImportFile($payloadDetails, $post);
    echo PHP_EOL . PHP_EOL;
    echo 'endpoint: ' . $endpoint, PHP_EOL;
    echo 'cURLparameters: ' . print_r($cURLparameters, TRUE), PHP_EOL;
    echo 'post: ' . print_r($post, TRUE), PHP_EOL;
    
    $this->assertTrue(TRUE);
  }
 
}
