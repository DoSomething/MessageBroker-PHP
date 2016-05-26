<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use \Exception;
 
/**
 *
 */
abstract class MB_Toolbox_BaseService
{
  
  /**
   * The messaging queue to send transactional requests based on the service.
   *
   * @var object $transactionalQueue
   */
  protected $transactionQueue;
  
  /**
   * 
   *
   * @var object $mbConfig
   */
  protected $mbConfig;

  /**
   * Collection of tools related to the Message Broker system.
   *
   * @var object $mbToolbox
   */
  protected $mbToolbox;

  /**
   * Collection of tools related to the Message Broker system cURL functionality.
   *
   * @var object $mbToolboxCURL
   */
  protected $mbToolboxCURL;

  /**
   * Message Broker object for logging settings.
   *
   * @var object $mbLoggingConfig
   */
  protected $mbLoggingConfig;

  /**
   * Connection to StatHat service for reporting monitoring counters.
   *
   * @var object $statHat
   */
  protected $statHat;


  /**
   *
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();
    
    $this->mbToolbox = $this->mbConfig->getProperty('mbToolbox');
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $this->mbLoggingConfig = $this->mbConfig->getProperty('mb_logging_api_config');
    $this->statHat = $this->mbConfig->getProperty('statHat');
  }

 /**
  * generateMessage(): Generate message values based on target service.
  *
  * @param string $address
  *   The specific user address of the medium the service communicates with.
  * @param array $messageDetails
  *   Settings used to construct the message contents.
  */
  abstract function generateDigestMessage($address, $messageDetails);

 /**
  * dispatchDigestMessage(): Send message to transactional queue.
  *
  * @param array $message
  *   The values to send as a message to the transactional queue.
  */
  abstract function dispatchDigestMessage($message);

 /**
  * generateSingleMessage(): Generate message values based on target service.
  *
  * @param string $address
  *   The specific user address of the medium the service communicates with.
  * @param array $messageDetails
  *   Settings used to construct the message contents.
  */
  abstract function generateSingleMessage($address, $messageDetails);

 /**
  * dispatchSingleMessage(): Send message to transactional queue.
  *
  * @param array $message
  *   The values to send as a message to the transactional queue.
  */
  abstract function dispatchSingleMessage($message);

  /**
   * getTeamplate(): Gather base template values by loading include (inc) file.
   *
   * @param string $templateFile
   *   The name of the file to load.
   *
   * @return string $markup
   *   The contents of the loaded file.
   */
  protected function getTemplate($templateFile) {

    $targetFile = __DIR__ . '/../templates/' . $templateFile;
    try {
      $markup = file_get_contents($targetFile);
    }
    catch(Exception $e) {
      throw new Exception('MB_Toolbox_BaseService->getTemplate(): Failed to load template: ' . $templateFile);
    }

    return $markup;
  }

}
