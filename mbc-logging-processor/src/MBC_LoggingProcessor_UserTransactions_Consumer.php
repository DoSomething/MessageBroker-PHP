<?php
/**
 * MBC_LoggingProcessor_UserTransactions_Consumer:
 * 
 */

namespace DoSomething\MBC_LoggingProcessor;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use \Exception;

/**
 * MBC_LoggingProcessor_UserTransactions_Consumer class - .
 */
class MBC_LoggingProcessor_UserTransactions_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * Connection to loggingGatewayExchange.
   *
   * @var object $mbLoggingGateway
   */
  private $mbLoggingGateway;

  /**
   * Values for message to be sent to LoggingGatewayExchange.
   *
   * @var array $submission
   */
  private $submission;

  /**
   * Constructor for MBC_LoggingGateway
   */
  public function __construct() {

    parent::__construct();
    $this->mbLoggingGateway = $this->mbConfig->getProperty('messageBroker_LoggingGateway');
  }

  /**
   * Callback for messages arriving in the LoggingQueue.
   *
   * @param string $payload
   *   A seralized message to be processed.
   */
  public function consumeLoggingQueue($payload) {

    echo '-------  mbc-logging-processor_userTransactionals -  MBC_LoggingProcessor_UserTransactions_Consumer->consumeLoggingQueue() START -------', PHP_EOL;

    parent::consumeQueue($payload);
    $this->logConsumption('email');

    if ($this->canProcess()) {

      try {

        $this->setter($this->message);
        $this->process();
      }
      catch(Exception $e) {
        echo 'Error logging transaction for email address: ' . $this->message['email'] . '. Error: ' . $e->getMessage();

        // @todo: Send copy of message to "dead message queue" with details of the original processing: date,
        // origin queue, processing app. The "dead messages" queue can be used to monitor health.
      }

    }
    else {
      echo '- ' . $this->message['email'] . ' can\'t be processed, removing from queue.', PHP_EOL;
      $this->messageBroker->sendAck($this->message['payload']);

      // @todo: Send copy of message to "dead message queue" with details of the original processing: date,
      // origin queue, processing app. The "dead messages" queue can be used to monitor health.
    }

    echo '-------  mbc-logging-processor_userTransactionals -  MBC_LoggingProcessor_UserTransactions_Consumer->consumeLoggingQueue() END -------', PHP_EOL . PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (!(isset($this->message['email']))) {
      echo '- canProcess(), email not set.', PHP_EOL;
      return FALSE;
    }
    if (!(isset($this->message['activity']))) {
      echo '- canProcess(), activity not set.', PHP_EOL;
      return FALSE;
    }

    $transactionActivities = [
      'user_register',
      'user_password',
      'campaign_signup',
      'campaign_reportback',
    ];
    if (!(in_array($this->message['activity'], $transactionActivities))) {
      echo '- canProcess(), activity not user transaction.', PHP_EOL;
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Construct values for submission to email service.
   *
   * @param array $message
   *   The message to process based on what was collected from the queue being processed.
   */
  protected function setter($message) {

    $this->submission = [];
    $this->submission['email'] = $message['email'];
    $this->submission['log-type'] = 'transactional';
    $this->submission['activity'] = $message['activity'];
    $this->submission['activity_timestamp'] = $message['activity_timestamp'];
    $this->submission['message'] = serialize($message['original']);

    if (isset($message['mobile'])) {
      $this->submission['mobile'] = $message['mobile'];
    }
    if (isset($message['source'])) {
      $this->submission['source'] = $message['source'];
    }
    elseif (isset($message['origin'])) {
      $this->submission['source'] = $message['origin'];
    }
    elseif (isset($message['user_country']) && ($message['user_country'] == 'MX' || $message['user_country'] == 'BR')) {
      $this->submission['source'] = $message['user_country'];
    }
    elseif (isset($message['application_id'])) {
      $this->submission['source'] = $message['application_id'];
    }
  }

  /**
   * process(): Gather message settings into submission to mb-logging-api
   */
  protected function process() {

    $message = serialize($this->submission);
    $this->mbLoggingGateway->publish($message);

    // $this->statHat->ezCount('mbc-logging-processor: process()', 1);
    $this->messageBroker->sendAck($this->message['payload']);
  }

  /**
   * logConsumption(): Extend to log the status of processing a specific message
   * element as well as the user_country and country.
   *
   * @param string $targetName
   */
  protected function logConsumption($targetName = NULL) {

    if (isset($this->message[$targetName]) && $targetName != NULL) {
      echo '** Consuming ' . $targetName . ': ' . $this->message[$targetName];
      if (isset($this->message['user_country'])) {
        echo ' from: ' .  $this->message['user_country'];
      }
      if (isset($this->message['activity'])) {
        echo ' doing: ' .  $this->message['activity'];
      }
      echo PHP_EOL;
    } else {
      echo '- logConsumption tagetName: "' . $targetName . '" not defined.', PHP_EOL;
    }
  }

}
