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
   * Initial method triggered by blocked call in mbc-registration-mobile.php.
   *
   * @param array $payload
   *   The contents of the queue entry message being processed.
   */
  public function consumeMessagingGroupsQueue($payload) {
    echo '------ messaging-groups-consumer - MessagingGroupsConsumer->consumeRegistrationMobileQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    parent::consumeQueue($payload);

    try {

      if ($this->canProcess($this->message)) {

        $this->setter($this->message);
        $this->process($this->mobileMessage);
        // Cleanup for next message
        unset($this->mobileMessage);
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: process', 1);

        // Ack in Service process() due to nested try/catch
      }
      else {
        echo '- failed canProcess(), removing from queue.', PHP_EOL;
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: skipping', 1);
        $this->messageBroker->sendAck($this->message['payload']);
      }
    }
    catch(Exception $e) {
      /**
       * The following code block is just awful.
       * It's legacy and we'll get rid of it soon.
       * | | |
       * V V V
       */

      if (!(strpos($e->getMessage(), 'Connection timed out') === false)) {
        echo '** Connection timed out... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: Exception: Connection timed out', 1);
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      }
      elseif (!(strpos($e->getMessage(), 'Operation timed out') === false)) {
        echo '** Operation timed out... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: Exception: Operation timed out', 1);
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      }
      elseif (!(strpos($e->getMessage(), 'Failed to connect') === false)) {
        echo '** Failed to connect... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: Exception: Failed to connect', 1);
      }
      elseif (!(strpos($e->getMessage(), 'Bad response - HTTP Code:500') === false)) {
        echo '** Connection error, http code 500... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: Exception: Bad response - HTTP Code:500', 1);
      }
      else {
        echo '- Not timeout or connection error, message to deadLetterQueue: ' . date('j D M Y G:i:s T'), PHP_EOL;
        echo '- Error message: ' . $e->getMessage(), PHP_EOL;

        // Uknown exception, save the message to deadLetter queue.
        $this->statHat->ezCount('messaging-groups-consumer: MessagingGroupsConsumer: Exception: deadLetter', 1);
        parent::deadLetter($this->message, 'MessagingGroupsConsumer->consumeRegistrationMobileQueue() Error', $e);

        // Send Negative Acknowledgment, don't requeue the message.
        $this->messageBroker->sendNack($this->message['payload'], false, false);
      }
    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueStatus = parent::queueStatus('consumeMessagingGroupsQueue');

    echo  PHP_EOL . '------ messaging-groups-consumer - MessagingGroupsConsumer->consumeRegistrationMobileQueue() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
  }

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
