<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;
 
/**
 *
 */
class MB_Toolbox_FBMessenger extends MB_Toolbox_BaseService
{

  /**
   *
   */
  public function __construct() {

    parent::__construct();
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalOTTQueue');
  }

 /**
  * generateMessage(): Generate message values based on Facebook Messenger requirements.
  *
  * @param array $settings
  */
 public function generateMessage($settings) {

   return $message;
 }
 
 /**
  * dispatchMessage(): Send message to Twilio to trigger sending transactional Facebook Messenger message.
  *
  * @param array $message
  *   Values to create message for processing in ottTransactionalQueue.
  */
  public function dispatchMessage($message) {
  
  
 }
 
}
