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
    $this->campaignTempateDivider = parent::getTemplate('campaign-divider-markup.mandrill.inc');

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

  /*
   * getTransactionalDigestMessageSubject(): Generate the message subject text.
   *
   * @return string $subject
   *   The dynamically generated message subject based on a list that
   *   will change weekly.
   */
  private function getTransactionalDigestMessageSubject() {

    $subjects = array(
      'Your weekly DoSomething campaign digest',
      'Your weekly DoSomething.org campaign roundup!',
      'A weekly campaign digest just for you!',
      'Your weekly campaign digest: ' . date('F j'),
      date('F j') . ': Your weekly campaign digest!',
      'Tips for your DoSomething.org campaigns!',
      'Comin atcha: tips for your DoSomething.org campaign!',
      '*|FNAME|* - Its your ' . date('F j') . ' campaign digest',
      'Just for you: DoSomething.org campaign tips',
      'Your weekly campaign tips from DoSomething.org',
      date('F j') . ': campaign tips from DoSomething.org',
      'You signed up for campaigns. Heres how to rock them!',
      'Tips for you (and only you!)',
      'Ready for your weekly campaign tips?',
      'Your weekly campaign tips: comin atcha!',
      'Fresh out the oven (just for you!)',
    );
    // Sequentially select an item from the list of subjects, a different one
    // every week and start from the top once the end of the list is reached
    $subjectCount = round((date('W') * count($subjects)) / 52);

    return $subjects[$subjectCount];
  }

  /*
   * An array of string to tag the message with. Stats are accumulated using
   * tags, though we only store the first 100 we see, so this should not be
   * unique or change frequently. Tags should be 50 characters or less. Any
   * tags starting with an underscore are reserved for internal use and will
   * cause errors.
   *
   * @return array $tags
   *   A list of tags to be associated with the digest messages.
   */
  private function getTransactionalDigestMessageTags() {

    $tags = array(
      0 => 'digest',
    );

    return $tags;
  }

  /*
   * getTransactionalDigestMessageFrom(): Generate the message from name and email address.
   *
   * @return array $from
   *   String values of the sender of the digest message.
   */
  private function getTransactionalDigestMessageFrom() {

    $from = [
      'email' => 'noreply@dosomething.org',
      'name' => 'Ben, DoSomething.org'
    ];

    return $from;
  }

  /**
   * getUsersTransactionalDigestSettings(): Generate "to" and "merge_var" values using the same index to ensure
   * the indexes match.
   *
   * @return array $userDigestSettings
   *   Formatted values based on Mandrill API requirements.
   */
  private function getTransactionalUsersDigestSettings() {

    $messageIndex = 0;
    $to = [];
    $mergeVars = [];

    if (!(isset($this->users)) || count($this->users) == 0) {
      throw new Exception('getUsersTransactionalDigestSettings() $this->users not set.');
    }
    else {
      foreach($this->users as $user) {
        $to[$messageIndex] = $this->setTo($user);
        $mergeVars[$messageIndex] = $this->getUserMergeVars($user);
        $messageIndex++;
      }

      $userTransactionalDigestSettings = [
        'to' => $to,
        'merge_vars' => $mergeVars,
      ];

      return $userTransactionalDigestSettings;
    }
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
   * getGlobalMergeVars(): Formatted global merge var values based on
   * Mandrill send-template API spec:
   * https://mandrillapp.com/api/docs/messages.JSON.html#method=send-template
   *
   * Global merge variables to use for all recipients. You can override these
   * per recipient.
   *
   * @return array $globalMergeVars
   *   A formatted array of global merge var values to be sent with
   *   digest batch.
   *
   */
  private function getGlobalMergeVars() {

    foreach($this->globalMergeVars as $name => $content) {
      $globalMergeVars[] = [
        'name' => $name,
        'content' => $content
      ];
    }

    return $globalMergeVars;
  }

  /*
   * composeTransactionalDigestBatch(): Assemble all of the parts to create a sendTemplate submission to
   * the Mandrill API.
   *
   * @return array
   *   All of the composed parts.
   */
  private function composeTransactionalDigestBatch() {

    // subject line
    $subject = $this->getTransactionalDigestMessageSubject();

    // from_email
    // from_name
    $from = $this->getDigestMessageFrom();

    // Gather user settings in single request to ensure "to" and "marge_vars" are in sync
    $usersDigestSettings = $this->getUsersTransactionalDigestSettings();
    $to = $usersDigestSettings['to'];
    $userMergeVars = $usersDigestSettings['merge_vars'];

    // global merge vars
    $globalMergeVars = $this->getGlobalMergeVars();

    // tags
    $tags = $this->getTransactionalDigestMessageTags();

    $composedDigestSubmission = array(
      'subject' => $subject,
      'from_email' => $from['email'],
      'from_name' => $from['name'],
      'to' => $to,
      'global_merge_vars' => $globalMergeVars,
      'merge_vars' => $userMergeVars,
      'tags' => $tags,
    );

    return $composedDigestSubmission ;
  }

 /**
  * dispatchMessages(): Send message to transactionalQueue to trigger sending transactional email messages.
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function dispatchMessages($message) {

    $templateName = '';
    // Must be included in submission but is kept blank as the template contents
    // are managed through the Mailchip/Mandril WYSIWYG interface.
    $templateContent = array(
      array(
          'name' => 'main',
          'content' => ''
      ),
    );

    try {
      $composedDigestBatch = $this->composeTransactionalDigestBatch();
 /// SEND TO transactionalQueue     $mandrillResults = $this->mandrill->messages->sendTemplate($templateName, $templateContent, $composedDigestBatch);
    }
    catch (Exception $e) {
      echo '- MB_Toolbox_MandrillService->dispatchMessages(): Error sending composed transactional digest batch: ' . $e->getMessage(), PHP_EOL;
    }

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

