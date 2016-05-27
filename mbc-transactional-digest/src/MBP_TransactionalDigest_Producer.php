<?php
/**
 * The TransactionalDigest collection of classes ...
 */
namespace DoSomething\MBP_UserDigest;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseProducer;

/*
 * MBP_TransactionalDigest_Producer: 
 */
class MBP_TransactionalDigest_Producer extends MB_Toolbox_BaseProducer
{
  
  /**
   * startTime - The number of time a shim message has been generated since the class was
   * instantiated.
   *
   * @var integer $shimCount
   */
  private $shimCount;
  
  /**
   * Constructor for MBP_TransactionalDigest_Producer - .
   */
  public function __construct() {

    perent::__construct();
    $this->shimCount = 0;
  }

  /**
   *  produceShim() - 
   */
  public function produceShim() {

    $routingKey = 'transactionalDigest';
    $payload = $this->generatePayload();
    $payload = parent::produceMessage($payload, $routingKey);
  }
  
  /**
   * generatePayload(): Format message payload
   *
   * @return array
   *   Formatted payload
   */
  protected function generatePayload() {

    $payload = parent::generatePayload();
    $payload['shimCount'] = $this->shimCount++;

    return $payload;
  }

}
