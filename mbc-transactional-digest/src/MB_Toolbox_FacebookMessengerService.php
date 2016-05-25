<?php
/**
 * A Service class used to generate messages based on the specific Service requirements. Each Service
 * has specifics based on the mediums they support and their API requirements.
 *
 * Facebook Messenger communication is provided through the Twilio API.
 */

namespace DoSomething\MBC_TransactionalDigest;
 
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
  * generateMessage(): Generate message values based on Facebook Messenger requirements.
  *
  * @param array $settings
  */
  public function generateMessage($address, $campaignsMarkup) {

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
