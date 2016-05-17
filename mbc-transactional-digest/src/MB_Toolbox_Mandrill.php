<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;
 
/**
 *
 */
class MB_Toolbox_Mandrill extends MB_Toolbox_BaseService
{

  /**
   *
   */
  public function __construct() {

    parent::__construct();
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalEmailQueue');
  }

 /**
  * generateMessage(): Generate message values based on Mandrill Send-Template requirements.
  *
  * @param array $settings
  *   Values to be used to generate message markup based on Mandrill API documentation:
  *   Send-Template: https://mandrillapp.com/api/docs/messages.JSON.html#method=send-template
  *   https://mandrillapp.com/api/docs/messages.JSON.html#method=send-template
  *
  *     - activity => 'campaign_signup_digest'
  *     - email => 'xxx@yahoo.com'
  *     - merge_vars =>
  *       - INTRODUCTION
  *       - CAMPAIGNS
  *       - RECOMMENDATIONS
  *     - user_language => 'en'
  *     - email_template => 'mb-transactional-digest-v0-0-1'
  *     - tags
  *       - drupal_campaign_signup
  *       - transactional_digest
  *     - activity_timestamp
  *     - application_id => US
  *
  *   Note: There's now support for "long SMS messages" of 2500 characters.
  */
 public function generateMessage($settings) {
   
   return $markup;
 }
 
 /**
  * dispatch(): Send message to transactionalQueue to trigger sending transactional Mobile Commons message.
  *
  *   Send SMS Message: https://secure.mcommons.com/api/send_message
  *   https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#SendSMSMessage.
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function dispatchMessage($message) {
  
  
 }
 
}

/*
(
    [activity] => campaign_signup
    [email] => xxx@yahoo.com
    [uid] => 3887635
    [merge_vars] => Array
        (
            [MEMBER_COUNT] => 5.3 million
            [FNAME] => Emily
            [CAMPAIGN_TITLE] => Bubble Breaks
            [CAMPAIGN_LINK] => https://www.dosomething.org/campaigns/bubble-breaks?source=node/1524
            [CALL_TO_ACTION] => Create homemade bubble blowing kits for kids at a family shelter. 
            [STEP_ONE] => Create and Decorate!
            [STEP_TWO] => Snap a Pic
            [STEP_THREE] => Drop It Off
        )

    [user_country] => US
    [user_language] => en
    [campaign_language] => en-global
    [campaign_country] => global
    [email_template] => mb-campaign-signup-US
    [subscribed] => 1
    [event_id] => 1524
    [email_tags] => Array
        (
            [0] => 1524
            [1] => drupal_campaign_signup
        )

    [mobile] => 1234567890
    [activity_timestamp] => 1463499802
    [application_id] => US
)
*/

