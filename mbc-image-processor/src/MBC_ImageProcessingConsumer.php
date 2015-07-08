<?php
namespace DoSomething\MBC_ImageProcessor;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MMBC_ImageProcessor\MBC_ImageProcessor;

/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
class MBC_ImageProcessingConsumer extends MBC_BaseConsumer
{

  /**
   * Message Broker connection to RabbitMQ
   */
  private $imagePath;

  /**
   * Initial method triggered by blocked call in mbc-image-processor.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  private function consumeImageProcessingQueue() {
    
    echo '- mbc-image-processor - MBC_ImageProcessingConsumer->consumeImageProcessingQueue() START', PHP_EOL;

    parent::consumeQueue();
    $this->setter($this->message);

    $ip = new MBC_ImageProcessor($this->statHat, $this->toolbox, $this->settings);
    $ip->process();
    unset($ip);
    
    echo '- mbc-image-processor - MBC_ImageProcessingConsumer->consumeImageProcessingQueue() STOP', PHP_EOL;
  }
  
  /**
   * Sets values ofr processing based on contents of message from consumed queue.
   */
  private function setter($message) {

    $imageMarkup = $message['merge_vars']['REPORTBACK_IMAGE_MARKUP'];
    $imagePath = substr($imageMarkup, 10, strpos($imageMarkup, '.jpg?') + 5);
    $this->imagePath = $imagePath;
  }

}
