<?php
namespace DoSomething\MBC_ImageProcessor;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
class MBC_ImageProcessor
{

  /**
   * Method to process image details. Make requests to trigger image cache processing on the Drupal site.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function process() {

    // An example cURL POST to the Message Broker User API (mb-user-api) to store peristant
    // Message Broker relivant data using the MB_Toolbox library.
    $curlUrl = $this->settings['ds_user_api_host'];
    $port = $this->settings['ds_user_api_port'];
    if ($port != 0) {
      $curlUrl .= ":$port";
    }
    $result = $this->toolbox->curlGETImage($curlUrl, $post);

    // Log consumer activity to StatHat for monitoring
    $this->statHat->clearAddedStatNames();
    if ($result[1] == 200) {
      $this->statHat->addStatName('success');
    }
    else {
      echo '** FAILED to update ?? for email: ' . $post['email'], PHP_EOL;
      echo '------- mbc-a1-startHere - MBC_A1_StartHere->startHere: $post: ' . print_r($post, TRUE) . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
      $this->statHat->addStatName('update failed');
    }
    $this->statHat->reportCount(1);
    
  }

}
