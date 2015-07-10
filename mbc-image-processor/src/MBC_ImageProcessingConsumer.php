<?php
namespace DoSomething\MBC_ImageProcessor;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBC_ImageProcessor\MBC_BaseConsumer;
use DoSomething\MBC_ImageProcessor\MBC_ImageProcessor;

/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
class MBC_ImageProcessingConsumer extends MBC_BaseConsumer
{

  /**
   * The image and http path to request.
   */
  protected $imagePath;

  /**
   * Initial method triggered by blocked call in mbc-image-processor.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeImageProcessingQueue($message) {

    echo '- mbc-image-processor - MBC_ImageProcessingConsumer->consumeImageProcessingQueue() START', PHP_EOL;

    // Limit the message rate per second to prevent overloading the Drupal app with image requeuests.
    $this->throttle(10);

    parent::consumeQueue($message);
    $this->setter($this->message);

    $ip = new MBC_ImageProcessor($this->messageBroker,  $this->statHat,  $this->toolbox, $this->settings);
    $ip->setImagePath ($this->imagePath);
    $ip->process($this->imagePath);

    // Log processing of image
    // $ip->log();

    // Destructor?
    unset($ip);

    echo '- mbc-image-processor - MBC_ImageProcessingConsumer->consumeImageProcessingQueue() STOP', PHP_EOL;
  }

  /**
   * Sets values ofr processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the message being processed.
   */
  protected function setter($message) {

    $imageMarkup = $message['merge_vars']['REPORTBACK_IMAGE_MARKUP'];
    $imgTagOffset = 10;
    $imagePath = substr($imageMarkup, $imgTagOffset, strpos($imageMarkup, '?') - $imgTagOffset);
    $imagePath = str_replace('https://dosomething-a.akamaihd.net', 'https://www.dosomething.org', $imagePath);

    $this->imagePath = $imagePath;
  }

  /**
   * Method to process image.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  protected function process() {
  }

}
