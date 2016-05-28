<?php
/**
 * The TransactionalDigest collection of classes ...
 */
namespace DoSomething\MBC_TransactionalDigest;

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

    parent::__construct();
    $this->shimCount = 0;
  }

  /**
   *  produceShim() - 
   */
  public function produceShim() {

    $routingKey = 'campaign.signup.transactional';
    $data = null;
    $payload = $this->generatePayload($data);
    $payload = parent::produceMessage($payload, $routingKey);
  }
  
  /**
   * generatePayload(): Format message payload
   *
   * @return array
   *   Formatted payload
   */
  protected function generatePayload($data) {

    $payload = parent::generatePayload($data);
    $payload['shimCount'] = $this->shimCount++;
    $payload['activity'] = 'shim';

    echo '- shimCount: ' . $this->shimCount, PHP_EOL;

    return $payload;
  }

}
