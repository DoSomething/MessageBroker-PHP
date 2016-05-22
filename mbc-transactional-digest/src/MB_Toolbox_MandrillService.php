<?php
/**
 * A Service class used to generate messages based on the specific Service requirements. Each Service
 * has specifics based on the mediums they support and their API requirements.
 *
 * Mandrill is the email engine made available by MainChimp that's accessed through an
 * API: https://mandrillapp.com/api/docs/.
 */

namespace DoSomething\MBC_TransactionalDigest;

/**
 * The MB_Toolbox_MandrillService class. A collection of functionality related to email and the
 * Mandrill service.
 */
class MB_Toolbox_MandrillService extends MB_Toolbox_BaseService
{

  /**
   * Loaded campaign HTML markup from inc file.
   * @var string $campaignMarkup
   */
  private $campaignMarkup;

  /**
   * Loaded campaign divider HTML markup from inc file.
   * @var string $campaignTempateDivider
   */
  private $campaignTempateDivider;

  /**
   * Setup common settings used throughout the class.
   */
  public function __construct() {

    parent::__construct();
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalEmailQueue');

    $this->campaignMarkup = parent::getTemplate('campaign-markup.mandrill.inc');
    $this->campaignTempateDivider = parent::getTemplate('campaign-divider-markup.mandrill.inc');
  }

 /**
  * generateCampaignMarkup(): Generate campaign specific row HTML markup for email.
  *
  * @param object $campaign
  *   Campaign values to be used to generate campaign row markup.
  *
  * @return string $markup
  *   HTML markup
  */
  public function generateCampaignMarkup($campaign) {

    $campaignMarkup = $this->campaignMarkup;
    
    $campaignMarkup = str_replace('*|CAMPAIGN_IMAGE_URL|*', $campaign->image_campaign_cover, $campaignMarkup);
    $campaignMarkup = str_replace('*|CAMPAIGN_TITLE|*', $campaign->title, $campaignMarkup);
    $campaignMarkup = str_replace('*|CAMPAIGN_LINK|*', $campaign->url, $campaignMarkup);
    $campaignMarkup = str_replace('*|CALL_TO_ACTION|*', $campaign->call_to_action, $campaignMarkup);
    
    if (isset($campaign->latest_news)) {
      $campaignMarkup = str_replace('*|TIP_TITLE|*',  'News from the team: ', $campaignMarkup);
      $campaignMarkup = str_replace('*|DURING_TIP|*',  $campaign->latest_news, $campaignMarkup);
    }
    else {
      $campaignMarkup = str_replace('*|TIP_TITLE|*',  $campaign->during_tip_header, $campaignMarkup);
      $campaignMarkup = str_replace('*|DURING_TIP|*',  $campaign->during_tip_copy, $campaignMarkup);
    }
    
    return $campaignMarkup;
  }

 /**
  * generateCampaignsMarkup(): Generate message values based on Mandrill Send-Template requirements.
  *
  * @param array $campaigns
  *   List of all user campaigns signed up for in current transactional batch.
  * @return string $campaignsMarkup
  *   All of the message campaigns formatted by the service requirements.
  */
  public function generateCampaignsMarkup($campaigns) {

    $campaignTempateDivider = $this->campaignTemplateDivider;
    $campaignsMarkup = null;
    $campaignCounter = 0;
    $totalCampaigns = count($campaigns);
    
    if ($totalCampaigns == 0) {
      throw new Exception('-> MB_Toolbox_MandrillService->generateCampaignsMarkup() no campaigns found.');
    }

    foreach ($campaigns as $campaignNID => $campaignMarkup) {
      $campaignsMarkup .= $campaignMarkup;
      
      // Add divider markup if more campaigns are to be added
      if ($totalCampaigns - 1 > $campaignCounter) {
        $campaignsMarkup .= $campaignTempateDivider;
      }
      $campaignCounter++;
    }

    return $campaignsMarkup;
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

    $markup = 'MANDRILL MESSAGE';

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

