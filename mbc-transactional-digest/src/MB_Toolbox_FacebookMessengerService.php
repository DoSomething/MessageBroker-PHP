<?php
/**
 * A Service class used to generate messages based on the specific Service requirements. Each Service
 * has specifics based on the mediums they support and their API requirements.
 *
 * Facebook Messenger communication is provided through the Twilio API.
 */

namespace DoSomething\MBC_TransactionalDigest;

use \Exception;
 
/**
 * The MB_Toolbox_FacebookMessengerService class. A collection of functionality related to Over
 * The Top (OTT) messaging using the Facebook Messenger service via Twilio.
 */
class MB_Toolbox_FacebookMessengerService extends MB_Toolbox_BaseService
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
    $this->transactionQueue = $this->mbConfig->getProperty('transactionalOTTQueue');

    $this->campaignMarkup = parent::getTemplate('campaign-markup.facebook-messenger.inc');
    $this->campaignTempateDivider = parent::getTemplate('campaign-divider-markup.facebook-messenger.inc');
  }

 /**
  * generateCampaignMarkup(): Generate campaign specific row HTML markup for email.
  *
  * @param object $campaign
  *   Campaign values to be used to generate campaign row markup.
  *
  * @return string $markup
  *   HTML markup
  *
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
  public function generateCampaignsMarkup($campaignsMarkup) {

    $campaignTempateDivider = $this->campaignTemplateDivider;
    $campaignsMarkup = null;
    $campaignCounter = 0;
    $totalCampaigns = count($campaigns);
    
    if ($totalCampaigns == 0) {
      throw new Exception('-> MB_Toolbox_FacebookMessengerService->generateCampaignsMarkup() no campaigns found.');
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
  * generateDigestMessage(): Generate message values based on Facebook Messenger requirements.
  *
  * @param string $address
  *   The specific user address of the medium the service communicates with.
  * @param array $messageDetails
  *   Settings used to construct the message contents.
  *
  * @return array $message
  *   Formatted message in format required by the service.
  */
  public function generateDigestMessage($address, $messageDetails) {

    $message['contents'] = 'FACEBOOK MESSENGER MESSAGE';

    return $message;
  }

 /**
  * dispatchDigestMessage(): Send message to Twilio to trigger sending transactional Facebook Messenger message.
  *
  * @param array $message
  *   Values to create message for processing in ottTransactionalQueue.
  */
  public function dispatchDigestMessage($message) {

 }

 /**
  * generateSingleMessages():
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function generateSingleMessage($address, $messageDetails) {

    // $this->transactionQueue->publish($message, 'user.registration.transactional');
  }

 /**
  * dispatchSingleMessages(): Send message to transactionalQueue to trigger sending transactional email message
  * in signle campaign signup format.
  *
  * @param array $message
  *   Values to create message for processing in transactionalQueue.
  */
  public function dispatchSingleMessage($payload) {

    // $this->transactionQueue->publish($message, 'user.registration.transactional');
  }

}
