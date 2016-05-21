<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;
 
/**
 *
 */
class MB_Toolbox_MobileCommons extends MB_Toolbox_BaseService
{

  /**
   *
   */
  public function __construct() {

    parent::__construct();
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalSMSQueue');
  }

 /**
  * generateMessage(): Generate message values based on Mobile Commons send_message() requirements.
  *
  * @param array $settings
  *   Values to be used to generate message markup based on Mobile Commons API documentation:
  *   Send SMS Message: https://secure.mcommons.com/api/send_message
  *   https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#SendSMSMessage.
  *
  *     body (160 characters or fewer. If passing body as a URL param, the value must be URL encoded)
  *
  *   Note: There's now support for "long SMS messages" of 2500 characters.
  */
 public function generateMessage($settings) {

  $message = '';

  /*
    campaign_id (Required) => fixed value, all basic digest SMS messages
    body => Text based list of user campaigns with opt in KEYWORDS
    phone_number (Required) => mobile
  */

   return $message;
 }
 
 /**
  * dispatchMessage(): Send message to mobileCommonsQueue to trigger sending transactional Mobile Commons message.
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
    [email] => xxx@gmail.com
    [uid] => 3404728
    [merge_vars] => Array
        (
            [MEMBER_COUNT] => 5.3 million
            [FNAME] => Jonice
            [CAMPAIGN_TITLE] => World Recycle Week: Close The Loop 
            [CAMPAIGN_LINK] => https://www.dosomething.org/us/campaigns/world-recycle-week-close-loop-0?source=node/362
            [CALL_TO_ACTION] => Recycle old or worn-out clothes to help our planet.
            [STEP_ONE] => Run Your Drive!
            [STEP_TWO] => Snap a Pic
            [STEP_THREE] => Drop It Off
        )

    [user_country] => US
    [user_language] => en
    [campaign_language] => en
    [campaign_country] => US
    [email_template] => mb-campaign-signup-US
    [subscribed] => 1
    [event_id] => 362
    [email_tags] => Array
        (
            [0] => 362
            [1] => drupal_campaign_signup
        )

    [mailchimp_list_id] => 8e7844f6dd
    [mailchimp_grouping_id] => 10641
    [mailchimp_group_name] => ComebackClothes2015
    [mobile] => 1234567890
    [mc_opt_in_path_id] => 203359
    [activity_timestamp] => 1463500108
    [application_id] => US
)

*/
