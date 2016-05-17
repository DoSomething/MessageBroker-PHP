<?PHP
/**
 * MBC_TransactionalDigest: Class to gather user campaign signup transactional message
 * requests into a single digest message for a given time period.
 */

namespace DoSomething\MBC_TransactionalDigest;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use \Exception;

/**
 * MBC_TransactionalDigest_Consumer class - functionality related to the Message Broker
 * consumer mbc-transactional-digest.
 */
class MBC_TransactionalDigest_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * mb-logging-api configuration settings.
   *
   * @var array $mbLoggingAPIConfig
   */
  private $mbLoggingAPIConfig;
  
    /**
   * A list of user objects.
   * @var array $users
   */
  private $users = [];

  /**
   * Constructor for MBC_LoggingGateway
   *
   * @param string $targetMBconfig
   *   The Message Broker object used to interface the RabbitMQ server exchanges and
   *   related queues.
   */
  public function __construct($targetMBconfig = 'messageBroker') {

    parent::__construct($targetMBconfig);
    $this->mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');

    $this->mbMessageServices['email'] = $this->mbConfig->getProperty('mbEmailService');
    $this->mbMessageServices['sms'] = $this->mbConfig->getProperty('mbSMSservice');
  }

  /**
   * Triggered when loggingGatewayQueue contains a message.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeQueue($payload) {

    echo '------- MBC_TransactionalDigest_Consumer - consumeQueue START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    parent::consumeQueue($payload);

    try {
      if ($this->canProcess()) {
        parent::logConsumption(['email', 'event_id']);
        $this->setter($this->message);
      }
      elseif ($this->message['log-type'] == 'shim') {
        echo '* Shim message encounter... time to sleep.', PHP_EOL;
        sleep(self::SHIM_SLEEP);
        $this->processShim();
      }
      else {
        echo '- ' . $this->message['log-type'] . ' can\'t be processed, sending to deadLetterQueue.', PHP_EOL;
        $this->statHat->ezCount('mbc-transactional-digest: MBC_LoggingGateway_Consumer: Exception: deadLetter', 1);
        parent::deadLetter($this->message, 'MBC_LoggingGateway_Consumer->consumeLoggingGatewayQueue() Error', $e->getMessage());

      }
    }
    catch(Exception $e) {
      echo 'Error sending transactional request to transactionalQueue, retrying... Error: ' . $e->getMessage();
      $this->statHat->ezCount('mbc-transactional-digest: MBC_TransactionalDigest_Consumer: Exception: ??', 1);
    }

    // Batch time reached, generate digest and dispatch messages to transactional queues
    try {
      if ($this->timeToProcess()) {
        $this->process();
      }
    }
    catch(Exception $e) {
      echo 'Error attempting to process transactional digest request. Error: ' . $e->getMessage();
      $this->statHat->ezCount('mbc-transactional-digest: MBC_TransactionalDigest_Consumer: Exception: ??', 1);
    }

    echo '------- MBC_TransactionalDigest_Consumer - consumeQueue() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (empty($this->message['email'])) {
      return false;
    }
    if (empty($this->message['activity'])) {
      return false;
    }
    if (isset($this->message['activity']) && $this->message['activity'] != 'campaign_signup') {
      return false;
    }
    if (empty($this->message['user_language'])) {
      return false;
    }

    if (isset($this->users[$this->message['email']][$this->message['event_id']])) {
      $message = 'MBC_TransactionalDigest_Consumer->canProcess(): Duplicate campaign signup for '.$this->message['email'].' to campaign ID: '.$this->message['event_id'];
      echo $message, PHP_EOL;
      throw new Exception($message);
    }

    return true;
  }

  /**
   * Construct values for submission to transactional message queues.
   *
   * @param array $message
   *   The message to process based on what was collected from the queue being processed.
   */
  protected function setter($message) {

    if (empty($this->users[$this->message['email']])) {
      $this->users[$this->message['email']] = $this->mbUserToolbox->gether($this->message);
    }

    if (empty($this->campaigns[$this->message['event_id']])) {
      $this->campaigns[$this->message['event_id']] = $this->mbCampaignToolbox->add($this->message['event_id']);
    }

    // Assigned by campaign IDs to email to define contents of transactional message
    $this->users[$this->message['email']][$this->message['event_id']] = [
      'event_id' => $this->message['event_id'],
      'markup' => [
        'email' =>  $this->campaigns[$this->message['event_id']]->markup['email'],
        'sms' => $this->campaigns[$this->message['event_id']]->markup['sms'],
      ],
    ];
 
  }

  /**
   * process(): Gather message settings into submission to mb-logging-api
   */
  protected function process() {

    // Build transactional requests for each of the users
    foreach ($this->users as $address => $messageDetails) {

      // Toggle between message services depending on communication medium - eMail vs SMS
      $medium = $this->whatMedium($address);
      $message = $this->mbMessageServices[$medium]->generateMarkup($messageDetails);
      $this->mbMessageServices[$medium]->dispatch($message);
    }
  }

  /**
   * timeToProcess: .
   *
   * @param array $payloadDetails
   *   ...
   *
   * @return array $
   *   ...
   */
  public function timeToProcess($payloadDetails) {

    $queuedMessages = parent::queueStatus('transactionalDigestQueue');

    return true;
  }

  /**
   * logTransactionalDigestMessage: Log transactional digest message contents by email address.
   *
   * @param array $payloadDetails
   *   ...
   *
   * @return array $
   *   ...
   */
  public function logTransactionalDigestMessage($payloadDetails) {


  }

}
