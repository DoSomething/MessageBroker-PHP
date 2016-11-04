<?php
/**
 * MessagingGroupsConsumer
 */

namespace DoSomething\MessagingGroupsConsumer;

use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\StatHat\Client as StatHat;

class MessagingGroupsConsumer extends MB_Toolbox_BaseConsumer
{

  /**
   * Method to determine if message can / should be processed. Conditions based on business
   * logic for submitted mobile numbers and related message values.
   *
   * @param array $message Values to determine if message can be processed.
   *
   * @retun boolean
   */
  protected function canProcess($message) {
    var_dump($message); die();
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the message being processed.
   */
  protected function setter($message) {

  }

  /**
   * Save all results to MoCo.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  protected function process($params) {

  }

}
