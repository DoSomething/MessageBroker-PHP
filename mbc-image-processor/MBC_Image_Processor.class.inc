<?php
/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBStatTracker\StatHat;

class MBC_Image_Processor
{

  /**
   * Message Broker connection to RabbitMQ
   */
  private $messageBroker;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Constructor for MBC_TransactionalEmail
   *
   * @param array $settings
   *   Settings from external services - StatHat
   */
  public function __construct($messageBroker, $settings) {

    $this->messageBroker = $messageBroker;
    $this->settings = $settings;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat($settings['stathat_ez_key'], 'mbc-a1-startHere:');
    $this->statHat->setIsProduction(isset($settings['use_stathat_tracking']) ? $settings['use_stathat_tracking'] : FALSE);
  }

  /**
   * Initial method triggered by blocked call in mbc-image-processor.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeImageProcessingQueue($payload) {
    
    echo '------- mbc-image-processor - consumeImageProcessingQueue() START -------', PHP_EOL;

    $payloadDetails = unserialize($payload->body);
    
    // Only process queue entries that have image details
    if ($payloadDetails['campaign_reportback'] && isset($payloadDetails['image_markup'])) {
      $this->processImage($payloadDetails['image_markup']);
    }
    
    echo '------- mbc-image-processor - consumeImageProcessingQueue() START -------', PHP_EOL;
  }

  /**
   * Method to process image details. Make requests to trigger image cache processing on the Drupal site.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function processImage($imageMarkup) {
  
    // Some processing of the message payload
    $post = array(
      'email' => '??',
    );

    // An example cURL POST to the Message Broker User API (mb-user-api) to store peristant
    // Message Broker relivant data using the MB_Toolbox library.
    $curlUrl = $this->settings['ds_user_api_host'];
    $port = $this->settings['ds_user_api_port'];
    if ($port != 0) {
      $curlUrl .= ":$port";
    }
    $curlUrl .= '/user';
    $result = $this->toolbox->curlPOST($curlUrl, $post);

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
