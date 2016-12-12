<?php
/**
 * DelayedEventsConsumer
 */

namespace DoSomething\DeadLetter;

use \Exception;

use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;

class DeadLetterFilter extends MB_Toolbox_BaseConsumer
{

  const TEXT_QUEUE_NAME = 'deadLetterQueue';

  /**
   * Constructor compatible with MBC_BaseConsumer.
   */
  public function __construct($targetMBconfig = 'messageBroker') {
    parent::__construct($targetMBconfig);
  }

  /**
   * Initial method triggered by blocked call in dead-letter-filter.
   *
   * @param array $messages
   *   The contents of the queue entry message being processed.
   */
  public function filterDeadLetterQueue($messages) {
    echo '------ dead-letter-filter - DelayedEventsConsumer->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    foreach ($messages as $key => $message) {
      $body = $message->getBody();
      if ($this->isSerialized($body)) {
        $payload = unserialize($body);
      } else {
        $payload = json_decode($body, true);
      }

      // Check that message is decoded correctly.
      if (!$payload) {
        echo 'Corrupted message: ' . $body . PHP_EOL;
        unset($messages[$key]);
        if (!DRY_RUN) {
          $this->messageBroker->sendNack($message, false, false);
        }
        continue;
      }

      // Check that message is qualified for this consumer.
      // if (!$this->canProcess($payload)) {
      //   echo '- canProcess() is not passed, removing from queue:' . $body . PHP_EOL;
      //   unset($messages[$key]);
      //   if (!DRY_RUN) {
      //     $this->messageBroker->sendNack($message, false, false);
      //   }
      //   continue;
      // }

      // Preprocess data.
      // $this->setter([$message, $payload]);

    }

    // Process data.
    $this->process([]);

    echo  PHP_EOL . '------ dead-letter-filter - DelayedEventsConsumer->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
  }

  /**
   * Method to determine if message can / should be processed. Conditions based on business
   * logic for submitted mobile numbers and related message values.
   *
   * @param array $message Values to determine if message can be processed.
   *
   * @retun boolean
   */
  protected function canProcess($payload) {

  }

  /**
   * Data processing logic.
   */
  protected function setter($arguments) {

  }

  /**
   * Forwards results to gambit.
   */
  protected function process($preprocessedData) {

  }

}
