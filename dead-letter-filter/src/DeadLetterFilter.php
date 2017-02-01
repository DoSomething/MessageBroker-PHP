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
   * Filter rules
   *
   * @var array
   */
  private $filter;

  /**
   * Constructor compatible with MBC_BaseConsumer.
   */
  public function __construct($targetMBconfig = 'messageBroker', $args) {
    parent::__construct($targetMBconfig);

    $this->filter = [];
    if (!empty($args['activity'])) {
      $this->filter['activity'] = $args['activity'];
    }
  }

  /**
   * Initial method triggered by blocked call in dead-letter-filter.
   *
   * @param array $messages
   *   The contents of the queue entry message being processed.
   */
  public function filterDeadLetterQueue($messages) {
    echo '------ dead-letter-filter - DelayedEventsConsumer->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    $this->messages = $messages;

    foreach ($this->messages as $key => $message) {
      $body = $message->getBody();
      if ($this->isSerialized($body)) {
        $payload = unserialize($body);
      } else {
        $payload = json_decode($body, true);
      }

      $original = &$payload['message'];

      // Check that message is decoded correctly.
      if (!$payload) {
        $this->log('Corrupted message: %s', $body);
        $this->reject($key);
        continue;
      }

      if (!$this->canProcess($payload)) {
        $this->log('Rejected: %s', json_encode($original));
        $this->reject($key);
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
    // $this->process([]);

    echo  PHP_EOL . '------ dead-letter-filter - DelayedEventsConsumer->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
  }

  protected function canProcess($payload) {
    // deadLetter v2:
    if (!empty($payload['message']) && !empty($payload['metadata'])) {
      $original = &$payload['message'];

      // Skip countries.
      $disabledAppIds = ['CA'];
      if (!empty($original['application_id'])
          && in_array($original['application_id'], $disabledAppIds)) {
        $this->log(
          'Skipping disabled application id %s',
          $original['application_id']
        );
        return false;
      }

    }

    return true;
  }

  /**
   * Log
   */
  static function log()
  {
    $args = func_get_args();
    $message = array_shift($args);
    echo '** ';
    echo vsprintf($message, $args);
    echo PHP_EOL;
  }

  private function reject($key) {
    if (!DRY_RUN) {
      $this->messageBroker->sendNack($this->messages[$key], false, false);
    }
    unset($this->messages[$key]);
  }

  /**
   * Bad OOP IS BAD.
   */
  protected function setter($arguments) {}
  protected function process($preprocessedData) {}

}
