<?php

use DoSomething\MBC_UserStatus\MBC_UserStatus;

    // Including that file will also return the autoloader instance, so you can store
    // the return value of the include call in a variable and add more namespaces.
    // This can be useful for autoloading classes in a test suite, for example.
    // https://getcomposer.org/doc/01-basic-usage.md
    $loader = require_once __DIR__ . '/../vendor/autoload.php';

class MBC_UserStatusTest extends PHPUnit_Framework_TestCase {
  public function setUp(){ }
  public function tearDown(){ }
  public function testSomeMethod()
  {
    $this->assertTrue(TRUE);
  }

}
