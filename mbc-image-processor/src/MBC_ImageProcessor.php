<?php
namespace DoSomething\MBC_ImageProcessor;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
class MBC_ImageProcessor extends MBC_ImageProcessingConsumer
{

  /**
   * Method to process image details. Make requests to trigger image cache processing on the Drupal site.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function process() {

    $result = MB_Toolbox_cURL::curlGETImage($this->imagePath);

    // Log consumer activity to StatHat for monitoring
    $this->statHat->clearAddedStatNames();
    if ($result[1] == 200) {
      $this->statHat->addStatName('success');
    }
    else {
      echo '** FAILED to GET ' . $this->imagePath  . ' image to trigger image style builds.', PHP_EOL;
      echo '------- mbc-a1-startHere - MBC_A1_StartHere->startHere: $post: ' . print_r($post, TRUE) . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
      $this->statHat->addStatName('update failed');
    }
    $this->statHat->reportCount(1);
    
  }

}
