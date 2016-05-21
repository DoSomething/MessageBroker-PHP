<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;
 
/**
 *
 */
class MB_Toolbox_FBMessengerService extends MB_Toolbox_BaseService
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
  * @param object $campaign
  *   Campaign values to be used to generate campaign row markup.
  *
  * @return string $markup
  *   HTML markup
  *
  */
  public function generateCampaignMarkup($campaign) {

    $campaignMarkup = parent::getTemplate('campaign-markup.facebook.inc');
    
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
