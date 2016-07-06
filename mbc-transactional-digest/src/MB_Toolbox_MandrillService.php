<?php
/**
 * A Service class used to generate messages based on the specific Service requirements. Each Service
 * has specifics based on the mediums they support and their API requirements.
 *
 * Mandrill is the email engine made available by MainChimp that's accessed through an
 * API: https://mandrillapp.com/api/docs/.
 */

namespace DoSomething\MBC_TransactionalDigest;

use \Exception;

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
  private $campaignTemplateDivider;

  /**
   * Settings common to all transactional digest messages.
   * @var array $globalMergeVars
   */
  private $globalMergeVars;

  /**
   * Setup common settings used throughout the class.
   */
  public function __construct() {

    parent::__construct();
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalEmailQueue');

    $this->campaignMarkup = parent::getTemplate('campaign-markup.mandrill.inc');
    $this->campaignTemplateDivider = parent::getTemplate('campaign-divider-markup.mandrill.inc');

    $this->setGlobalMergeVars();
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

  /*
   * An array of string to tag the message with. Stats are accumulated using
   * tags, though we only store the first 100 we see, so this should not be
   * unique or change frequently. Tags should be 50 characters or less. Any
   * tags starting with an underscore are reserved for internal use and will
   * cause errors.
   *
   * @return array $tags
   *   A list of tags to be associated with the transactional digest messages.
   */
  private function getTransactionalDigestMessageTags() {

    $tags = array(
      0 => 'transactional',
      1 => 'transactional-digest',
    );

    return $tags;
  }

  /**
   *
   */
  private function setGlobalMergeVars() {

    $memberCount = $this->mbToolbox->getDSMemberCount();
    $currentYear = date('Y');

    $this->globalMergeVars = [
      'MEMBER_COUNT' => $memberCount,
      'CURRENT_YEAR' => $currentYear,
    ];
  }

  /**
   * getUserMergeVars():
   *
   * @return array $mergeVars
   *
   */
  private function getMergeVars($campaignsMarkup, $mergeVars) {

    $digestMergeVars = [
      'FNAME' => $mergeVars['FNAME'],
      'CAMPAIGNS' => $campaignsMarkup,
      'MEMBER_COUNT' => $mergeVars['MEMBER_COUNT']
    ];

    return $digestMergeVars;
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
  public function generateDigestMessage($address, $messageDetails) {

    $template = 'mb-transactional-digest-v0-0-1';
    $mergeVars = $this->getMergeVars($messageDetails['campaignsMarkup'], $messageDetails['merge_vars']);
    $tags = $this->getTransactionalDigestMessageTags();

    $message = [
      'log-type' => 'transactional',
      'activity' => 'campaign_signup_digest',
      'email_template' => $template,
      'email' => $address,
      'merge_vars' => $mergeVars,
      'user_language' => 'en',
      'email_tags' => $tags,
      'activity_timestamp' => time(),
      'application_id' => 'MBC-TRANSACTIONAL-DIGEST'
    ];

    return $message;
  }

 /**
  * dispatchMessages(): Send message to transactionalQueue to trigger sending transactional email message.
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function dispatchDigestMessage($payload) {

    $message = json_encode($payload);
    $this->transactionQueue->publish($message, 'campaigns.signup-digest.transactional');
  }

 /**
  * generateSingleMessages():
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function generateSingleMessage($address, $messageDetails) {

  }

 /**
  * Send message to transactionalQueue to trigger sending transactional email message
  * in single campaign signup format.
  *
  * @param array $message Values to create message for processing in transactionalQueue.
  */
  public function dispatchSingleMessage($payload) {

    // $this->transactionQueue->publish($message, 'campaign.signup.transactional');
  }

}
