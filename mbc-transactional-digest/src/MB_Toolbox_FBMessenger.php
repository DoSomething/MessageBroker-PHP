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
  * generateCampaignMarkup(): Generate campaign specific row HTML markup for email.
  *
  * @param array $settings
  *   Campaign Values to be used to generate campaign row markup.
  *
  * @return string $markup
  *   HTML markup
  *
  */
   public function generateCampaignMarkup($settings) {

     $markup = ' MB_Toolbox_FBMessenger - GENERATED CAMPAIGN MARKUP';

     return $markup;
  }

 /**
  * generateCampaignsMarkup(): Generate message values based on Mandrill Send-Template requirements.
  *
  * @param array $settings
  *
  */
  public function generateCampaignsMarkup($settings) {

    $markup = ' MB_Toolbox_FBMessenger - CAMPAIGNS MARKUP';

    return $markup;
 }

 /**
  * generateMessage(): Generate message values based on Facebook Messenger requirements.
  *
  * @param array $settings
  */
  public function generateMessage($settings) {

    $markup = 'FACEBOOK MESSENGER MESSAGE';

    return $markup;
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
