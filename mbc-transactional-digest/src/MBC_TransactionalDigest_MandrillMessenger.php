<?php
/**
 * MBC_DigestEmail_MandrillMessenger
 * 
 */

namespace DoSomething\MBC_TransactionalDigest;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use \Exception;

/**
 * MBC_DigestEmail_MandrillMessenger class - 
 */
class MBC_TransactionalDigest_MandrillMessenger extends MBC_DigestEmail_BaseMessenger {

  // The maximum number of campaigns to include in a users digest message.
  const MAX_CAMPAIGNS = 5;

  /*
   * User object to send digest messages to.
   * @var array $users
   */
  private $users = [];

  /**
   * Campaign objects to reference when building digest message contents.
   * @var array $campaigns
   */
  private $campaigns = [];

  /**
   * Singleton instance of application configuration settings.
   * @var object $mbConfig
   */
  private $mbConfig;

  /**
   * Message Broker library of RabbitMQ methods.
   * @var object $messageBroker
   */
  private $messageBroker;

  /**
   * Library of methods to support reporting activity to StatHat service.
   * @var object $statHat
   */
  private $statHat;

  /**
   * Collection of methods used by applications withing the Message Broker system.
   * @var object $mbToolbox
   */
  private $mbToolbox;

  /**
   * Markup for a campaign rom in a user digest message.
   * @var string $campaignTempate
   */
  private $campaignTempate;

  /**
   * The divider markup that goes between campaign rows in a digest message.
   * @var string $campaignTempateDivider
   */
  private $campaignTempateDivider;

  /**
   * A collection of methods and properties for submissions to the Mandrill API.
   * @var Mandrill $mandrill
   */
  private $mandrill;

  /**
   * Common settings between all of the digest messages that will be merged into the template to help
   * compose the user message.
   * @var array $globalMergeVars
   */
  private $globalMergeVars;

  /*
   * Assemble methods and template markup to construct collection of user digest message
   * submission to the Mandrill email service.
   */
  public function __construct() {

    // Application configuration
    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBroker = $this->mbConfig->getProperty('messageBroker');
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->mbToolbox = $this->mbConfig->getProperty('mbToolbox');
    $this->mandrill = $this->mbConfig->getProperty('mandrill');

    // Resources for building digest batch
    $this->userIndex = 0;
    $this->campaignTempate = parent::getTemplate('campaign-markup.inc');
    $this->campaignTempateDivider = parent::getTemplate('campaign-divider-markup.inc');
    $this->setGlobalMergeVars();
  }

  /**
   * addUser(): Coordinate the the construction of a user object resulting
   * in an addition to the users list to base future batch messaging.
   *
   * @param object $user
   */
  public function addUser($user) {

    // Add user object into list of users to send batch message to.
    $this->users[$user->email] = $user;
    $this->userIndex++;

    $this->generateUserMergeVars($user->email);
  }

  /**
   * addCampaign(): Add campaign object to class campaigns property indexed by the
   * campaign Drupal nid (node ID).
   *
   * @param MBC_DigestEmail_Campaign (object) $campaign
   *   Details of a campaign based on details gathered from the Drupal site based on a Drupal
   *   nid (node ID).
   */
  public function addCampaign( MBC_DigestEmail_Campaign $campaign) {

    if (!(isset($this->campaigns[$campaign->drupal_nid]))) {
        $campaignNID = (string) $campaign->drupal_nid;
        $this->campaigns[$campaignNID] = $campaign;
        $this->generateCampaignMarkup($campaign);
    }
  }

  /**
   * generateCampaignMarkup(): Build campaign markup for Campaign object. To be used to
   * generate *|CAMPAIGNS|* user merge var.
   *
   * @param object $campaign
   */
  private function generateCampaignMarkup($campaign) {

    // Check for existing markup
    if (!(isset($this->campaigns[$campaign->drupal_nid]->markup))) {

      $campaignMarkup = $this->campaignTempate;

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

      $this->campaigns[$campaign->drupal_nid]->markup = $campaignMarkup;
    }
  }

  /**
   *
   */
  public function generateUserMergeVars($userEmail) {

    $firstName = $this->users[$userEmail]->firstName;
    $campaignsMarkup = $this->generateUserCampaignMarkup($userEmail);
    $unsubscribeLinkMarkup = 'http://' . $this->users[$userEmail]->subscription_link->url . '&utm_source=digest&utm_medium=email&utm_campaign=start20160512';

    $this->users[$userEmail]->merge_vars = [
      'FNAME' =>  $firstName,
      'CAMPAIGNS' => $campaignsMarkup,
      'SUBSCRIPTIONS_LINK' => $unsubscribeLinkMarkup,
    ];
  }

  /**
   * generateUserCampaignMarkup(): Generate markup for campaign listing based on specific user
   * campaigns settings.
   *
   * @param string $userEmail
   *   The email address of the target user. Used to access users class property which is
   *   indexed by the users email string.
   *
   * @return string $markup
   *   HTML string of the user campaigns. Used as body of digest email message for
   *   specific user.
   */
  private function generateUserCampaignMarkup($userEmail) {

    $markup = '';
    $campaignCounter = 0;
    $totalCampaigns = count($this->users[$userEmail]->campaigns);

    foreach($this->users[$userEmail]->campaigns as $nid => $campaign) {
      $markup .= $this->campaigns[$nid]->markup;

      // Add divider markup if more campaigns are to be added
      if ($totalCampaigns - 1 > $campaignCounter) {
        $markup .= $this->campaignTempateDivider;
      }
      $campaignCounter++;
    }

    return $markup;
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
   * setGlobalMergeVars(): Formatted global merge var values based on
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

  /**
   * getUsersDigestSettings(): Generate "to" and "merge_var" values using the same index to ensure the indexes match.
   *
   * @return array $userDigestSettings
   *   Formatted values based on Mandrill API requirements.
   */
  private function getUsersDigestSettings() {

    $messageIndex = 0;
    $to = [];
    $mergeVars = [];

    if (!(isset($this->users)) || count($this->users) == 0) {
      throw new Exception('getUsersDigestSettings() $this->users not set.');
    }
    else {
      foreach($this->users as $user) {
        $to[$messageIndex] = $this->setTo($user);
        $mergeVars[$messageIndex] = $this->getUserMergeVars($user);
        $messageIndex++;
      }

      $userDigestSettings = [
        'to' => $to,
        'merge_vars' => $mergeVars,
      ];

      return $userDigestSettings;
    }
  }

  /**
   * getUserMergeVars(): Structure user merge_var values based on Mandrill API.
   * https://mandrillapp.com/api/docs/messages.JSON.html#method=send-template
   *
   * @param object $user
   *   A user object with all user related settings.
   *
   *  @return array $userMergeVars
   *    Formatted user specific merge_var values.
   */
  private function getUserMergeVars($user) {

    $vars = [];
    foreach($user->merge_vars as $name => $value) {
      $vars[] = [
        'name' => $name,
        'content' => $value,
      ];
    }

    $userMergeVars = [
      'rcpt' => $user->email,
      'vars' => $vars
    ];

    return $userMergeVars;
  }

  /**
   * Construct $to array based on Mandrill send-template API specification.
   *
   * https://mandrillapp.com/api/docs/messages.JSON.html#method=send-template
   * "to": [
   *   {
   *     "email": "recipient.email@example.com",
   *     "name": "Recipient Name",
   *     "type": "to"
   *   }
   * ],
   *
   * "type": "to" - the header type to use for the recipient, defaults to "to"
   * if not provided one of(to, cc, bcc)
   *
   * @param array $targetUsers
   *   Details about user to send digest message to.
   *
   * @return array $to
   *   $to in Mandrill API structure.
   */
  private function setTo($user) {

    $to = [
      'email' => $user->email,
      'name' => $user->firstName,
      'to' => 'to',
    ];
    return $to;
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
  private function getDigestMessageTags() {

    $tags = array(
      0 => 'digest',
    );

    return $tags;
  }

  /*
   * getDigestMessageSubject(): Generate the message subject text.
   *
   * @return string $subject
   *   The dynamically generated message subject based on a list that
   *   will change weekly.
   */
  private function getDigestMessageSubject() {

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
   * getDigestMessageFrom(): Generate the message from name and email address.
   *
   * @return array $from
   *   String values of the sender of the digest message.
   */
  private function getDigestMessageFrom() {

    $from = [
      'email' => 'noreply@dosomething.org',
      'name' => 'Ben, DoSomething.org'
    ];

    return $from;
  }

  /*
   * composeDigestBatch(): Assemble all of the parts to create a sendTemplate submission to the Mandrill API.
   *
   * @return array
   *   All of the composed parts.
   */
  private function composeDigestBatch() {

    // subject line
    $subject = $this->getDigestMessageSubject();

    // from_email
    // from_name
    $from = $this->getDigestMessageFrom();

    // Gather user settings in single request to ensure "to" and "marge_vars" are in sync
    $usersDigestSettings = $this->getUsersDigestSettings();
    $to = $usersDigestSettings['to'];
    $userMergeVars = $usersDigestSettings['merge_vars'];

    // global merge vars
    $globalMergeVars = $this->getGlobalMergeVars();

    // tags
    $tags = $this->getDigestMessageTags();

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
   * sendDigestBatch(): Send all of the essential parts for a sendTeamplate submission to the Mandrill API.
   */
  public function sendDigestBatch() {

    $templateName = 'mb-digest-v0-5-1';
    // Must be included in submission but is kept blank as the template contents
    // are managed through the Mailchip/Mandril WYSIWYG interface.
    $templateContent = array(
      array(
          'name' => 'main',
          'content' => ''
      ),
    );
    try {
      $composedDigestBatch = $this->composeDigestBatch();

      $mandrillResults = $this->mandrill->messages->sendTemplate($templateName, $templateContent, $composedDigestBatch);
      $this->wrapUp($mandrillResults);
    }
    catch (Exception $e) {
      echo '- MBC_DigestEmail_MandrillMessenger->sendDigestBatch(): Error creating composed digest batch: ' . $e->getMessage(), PHP_EOL;
    }
  }

  /**
   * wrapUp(): Post submission to Mandrill service.
   *
   * @param array $results
   *   The results of the submission to the Mandrill API.
   */
  private function wrapUp($results) {

    echo 'Mandrill results: ' . print_r($results, TRUE), PHP_EOL;

    // Report send results to console
    $stats = [];
    foreach ($results as $sendStats) {
      if (isset($stats[$sendStats['status']])) {
        $stats[$sendStats['status']]++;
      }
      else {
        $stats[$sendStats['status']] = 1;
      }
    }
    echo 'mandrillResults: ' . print_r($stats, TRUE), PHP_EOL . PHP_EOL;
    unset($this->users);
  }
}
