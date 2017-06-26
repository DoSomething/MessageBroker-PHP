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
   * The amount of time in seconds between campaign signups that result in a digest message
   * of signups rather than individual transactional messages.
   */
  const TRANSACTIONAL_DIGEST_WINDOW = 300;

  /**
   * The time interval that collected signups are reviewed for generation of digest messages.
   * This is the potential maximum amount of time in seconds that can pass from the users
   * campaign signup before they receive a message.
   */
  const TRANSACTIONAL_DIGEST_CYCLE = 60;

  /**
   * mb-logging-api configuration settings.
   *
   * @var array $mbLoggingAPIConfig
   */
  private $mbMessageServices;

  /**
   *
   * @var object $mbCampaignToolbox
   */
  private $mbCampaignToolbox;
  
  /**
   * A list of user objects.
   * @var array $users
   */
  private $users = [];

  /**
   * mb-logging-api configuration settings.
   *
   * @var array $mbLoggingAPIConfig
   */
  private $mbLoggingAPIConfig;

  /**
   * The timestamp of the last time the list of user transactions was processed.
   *
   * @var init $lastProcessed
   */
  private $lastProcessed;

  /**
   * Constructor for MBC_LoggingGateway
   *
   * @param string $targetMBconfig
   *   The Message Broker object used to interface the RabbitMQ server exchanges and
   *   related queues.
   */
  public function __construct($targetMBconfig = 'messageBroker') {

    parent::__construct($targetMBconfig);

    $this->mbMessageServices['email'] = new MB_Toolbox_MandrillService();
    $this->mbMessageServices['sms'] = new MB_Toolbox_MobileCommonsService();
    $this->mbMessageServices['ott'] = new MB_Toolbox_FacebookMessengerService();

    $this->mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');
    $this->lastProcessed = time();
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
      if ($this->canProcess($this->message)) {
        parent::logConsumption(['email', 'event_id']);
        $this->setter($this->message);
        $this->messageBroker->sendAck($this->message['payload']);
      }
      elseif (isset($this->message['activity']) && $this->message['activity'] === 'shim') {
        echo '* Shim message encounter... thanks for the wakeup, removing from queue.', PHP_EOL;
        $this->messageBroker->sendAck($this->message['payload']);
      }
      elseif (isset($this->message['activity']) && $this->message['activity'] === 'campaign_signup_single') {
        echo '* Skipping own campaign_signup_single message, it\'s intended for the mbc-transactional-email.', PHP_EOL;
        $this->messageBroker->sendAck($this->message['payload']);
      }
      elseif ($this->message['application_id'] != 'US') {
        echo '* Non US application campaign signup, removing from queue.', PHP_EOL;
        $this->messageBroker->sendAck($this->message['payload']);
      }
      else {
        echo '- canProcess() is not passed, skipping the message.', PHP_EOL;
        $this->messageBroker->sendAck($this->message['payload']);

      }
    }
    catch(Exception $e) {
      if (strpos($e->getMessage(), 'returned 200 with rejected response.') !== false) {
        echo '- '.$e->getMessage(), PHP_EOL;
        // parent::deadLetter($this->message, 'MBC_LoggingGateway_Consumer->consumeLoggingGatewayQueue() Generation Error');
        $this->messageBroker->sendAck($this->message['payload']);
      }
      else {
        echo '- '.$e->getMessage(), PHP_EOL;
        $this->statHat->ezCount('mbc-transactional-digest: MBC_TransactionalDigest_Consumer: Exception: '.$e->getMessage(), 1);
        // parent::deadLetter($this->message, 'MBC_LoggingGateway_Consumer->consumeLoggingGatewayQueue() Generation Error');
        $this->messageBroker->sendAck($this->message['payload']);
      }
    }

    // Batch time reached, generate digest and dispatch messages to transactional queues
    try {
      if ($this->timeToProcess()) {

        // Pass in parameters to process() to allow for unit test coverage
        // @todo: Break out more params value to made process() autonomous
        $params['users'] = $this->users;
        $this->process($params);
        $this->lastProcessed = time();
      }
    }
    catch(Exception $e) {
      echo 'Error attempting to process transactional digest request. Error: ' . $e->getMessage();
      $this->statHat->ezCount('mbc-transactional-digest: MBC_TransactionalDigest_Consumer: Exception', 1);
      parent::deadLetter($this->message, 'MBC_TransactionalDigest_Consumer->consumeQueue() process() Error', $e->getMessage());
      $this->messageBroker->sendAck($this->message['payload']);
    }

    echo '------- MBC_TransactionalDigest_Consumer - consumeQueue() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @param array $message Message settings to be reviewed to determine if the message can be processed.
   *
   * @return boolean
   */
  protected function canProcess($message) {

    if (empty($message['email']) && empty($message['mobile']) && empty($message['ott'])) {
      return false;
    }

    // Exclude generated emails adresses.
    if (preg_match('/@.*\.import$/', $message['email'])) {
      echo '- canProcess(), import placeholder address: ' . $message['email'], PHP_EOL;
      return false;
    }

    if (empty($message['activity'])) {
      return false;
    }
    if (isset($message['activity']) && $message['activity'] != 'campaign_signup') {
      return false;
    }
    if ($message['application_id'] != 'US') {
      return false;
    }
    if (empty($message['user_language'])) {
      return false;
    }

    if (isset($this->users[$message['email']]['campaigns'][$message['event_id']])) {
      $errorMessage = 'MBC_TransactionalDigest_Consumer->canProcess(): Duplicate campaign signup for ' . $message['email'].' to campaign ID: ' . $message['event_id'];
      echo $errorMessage, PHP_EOL;
      throw new Exception($errorMessage);
    }

    $disabledCampaigns = [
      // Explain the Pain: Share
      // https://www.dosomething.org/us/campaigns/explain-pain-share
      7433,
      // DoSomething Rewards Challenge
      // https://www.dosomething.org/us/campaigns/dosomething-rewards-challenge
      7589,
      // Suspended for WHAT?: AMPLIFY
      // https://www.dosomething.org/us/campaigns/suspended-what-amplify
      7661,
      // Suspended for WHAT?: ADVOCATE
      // https://www.dosomething.org/us/campaigns/suspended-what-advocate
      7662,
      // Car Sticky Note Challange
      // https://www.dosomething.org/us/campaigns/car-sticky-note-challange
      7675,
      // Sincerely, Us
      // https://www.dosomething.org/us/campaigns/sincerely-us
      7656,
      // Unlock the Truth
      // https://www.dosomething.org/us/campaigns/uncover-truth
      7771,
      // Mirror Messages
      // https://www.dosomething.org/us/campaigns/mirror-messages
      7,
      // All in We Win
      // https://www.dosomething.org/us/campaigns/all-we-win
      7831,
      // Steps for Soldiers
      // https://www.dosomething.org/us/campaigns/steps-soldiers
      7822,
    ];
    if (in_array($message['event_id'], $disabledCampaigns)) {
      echo '- Campaign signup communication is disabled.' . PHP_EOL;
      return false;
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

    // Collection of campaign details and generation of markup by medium
    if (empty($this->campaigns[$message['event_id']])) {
      $this->campaigns[$message['event_id']] = new MB_Toolbox_Campaign($message['event_id']);
      $this->campaigns[$message['event_id']]->markup = [
        'email' => $this->mbMessageServices['email']->generateCampaignMarkup($this->campaigns[$message['event_id']]),
        'sms'   => $this->mbMessageServices['sms']->generateCampaignMarkup($this->campaigns[$message['event_id']]),
        'ott'   => $this->mbMessageServices['ott']->generateCampaignMarkup($this->campaigns[$message['event_id']]),
      ];
    }

    // Basic user settings by medium
    if (isset($message['email']) && empty($this->users[$message['email']])) {
      $this->users[$message['email']] = $this->gatherUserDetailsEmail($message);
      $this->users[$message['email']]['merge_vars'] = $message['merge_vars'];
    }
    if (isset($message['mobile']) && empty($this->users[$message['mobile']])) {
      $this->users[$message['mobile']] = $this->gatherUserDetailsSMS($message);
    }
    if (isset($message['ott']) && empty($this->users[$message['ott']])) {
      $this->users[$message['ott']] = $this->gatherUserDetailsOTT($message);
    }

    // Assign markup by medium for campaigns the user is signed up for
    if (isset($message['email']) && empty($this->users[$message['email']]->campaigns[$message['event_id']])) {
      $this->users[$message['email']]['campaigns'][$message['event_id']] = $this->campaigns[$message['event_id']]->markup['email'];
      $this->users[$message['email']]['last_transaction_stamp'] = time();
    }
    if (isset($message['mobile']) && empty($this->users[$message['mobile']]->campaigns[$message['event_id']])) {
      $this->users[$message['mobile']]['campaigns'][$message['event_id']] = $this->campaigns[$message['event_id']]->markup['sms'];
      $this->users[$message['mobile']]['last_transaction_stamp'] = time();
    }
    if (isset($message['ott']) && empty($this->users[$message['ott']]->campaigns[$message['event_id']])) {
      $this->users[$message['ott']]['campaigns'][$message['event_id']] = $this->campaigns[$message['event_id']]->markup['ott'];
      $this->users[$message['ott']]['last_transaction_stamp'] = time();
    }

  }

  /**
   * Gather message settings into submission to mb-logging-api
   *
   * @param array $params
   * @todo: Break out more params value to made process() autonomous
   */
  protected function process($params) {

    // Build transactional requests for each of the users
    foreach ($params['users'] as $address => $messageDetails) {
      echo '** Processing ' . $address . '.', PHP_EOL;

      if ($this->timeToProcessUser($messageDetails)) {

        // Digest messages are composed of at least two signups in the DIGEST_WINDOW. If only one
        // campaign signup, send message to transactionalQueue to send standard campaign signup
        // message.
        $medium = $this->whatMedium($address);
        if (count($messageDetails['campaigns']) > 1) {
          // Toggle between message services depending on communication medium - eMail vs SMS vs OTT
          $messageDetails['campaignsMarkup'] = $this->mbMessageServices[$medium]->generateCampaignsMarkup($messageDetails['campaigns']);
          echo '*** Sending ' . $medium . ' digest to ' . $address . '.', PHP_EOL;
          $message = $this->mbMessageServices[$medium]->generateDigestMessage($address, $messageDetails);
          $this->mbMessageServices[$medium]->dispatchDigestMessage($message);
        }
        else {
          echo '*** Sending normal transactional ' . $medium . ' to ' . $address . '.', PHP_EOL;
          $message = $this->mbMessageServices[$medium]->generateSingleMessage($address, $messageDetails);
          $this->mbMessageServices[$medium]->dispatchSingleMessage($message);
        }
        unset($this->users[$address]);
      }
      else {
         echo '*** Waiting some more for ' . $address . '.', PHP_EOL;
       }
    }
  }

  /**
   * Determine if the message being processed signals that's time to process. The results could be a
   * digest campaign signup message if there's more than one signup or forwarding the signup to the
   * transaction queue to generate a simple singe transactional campaign signup message.
   *
   * @param array $messageDetails Details of the message being processed.
   *
   * @returns boolean
   */
  private function timeToProcessUser($messageDetails) {

    if (empty($messageDetails['last_transaction_stamp'])) {
      return false;
    }

    $collectionTimeframe = time() - self::TRANSACTIONAL_DIGEST_WINDOW;

    // Was the last transaction still within the collection time frame?
    if ($messageDetails['last_transaction_stamp'] > $collectionTimeframe) {
      return false;
    }

    return true;
  }

  /**
   * gatherUserDetailsEmail: .
   *
   * @param array $message
   *   ...
   *
   * @return array $
   *   ...
   */
  public function gatherUserDetailsEmail($message) {

    $userDetails = [
      'campaigns' => [],
      'first_name' => $message['merge_vars']['FNAME'],
      'original_message' => $message['original'],
    ];

    return $userDetails;
  }

  /**
   * gatherUserDetailsSMS: .
   *
   * @param array $message
   *   ...
   *
   * @return array $
   *   ...
   */
  public function gatherUserDetailsSMS($message) {

    $userDetails = [
      'campaigns' => [],
      'first_name' => $message['merge_vars']['FNAME'],
    ];

    return $userDetails;
  }

  /**
   * gatherUserDetailsOTT: .
   *
   * @param array $message
   *   ...
   *
   * @return array $
   *   ...
   */
  public function gatherUserDetailsOTT($message) {

    $userDetails = [
      'campaigns' => [],
      'first_name' => $message['merge_vars']['FNAME']
    ];

    return $userDetails;
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
  public function timeToProcess() {

    if ($this->lastProcessed < (time() - self::TRANSACTIONAL_DIGEST_CYCLE)) {
      return true;
    }
    return false;
  }

  /**
   * whatMedium(): .
   *
   * @param string $address
   *   The address to analyze to determine what medium it is from.
   *
   * @return string $medium
   *   The determined medium for the $address.
   */
  public function whatMedium($address) {

    if (filter_var($address, FILTER_VALIDATE_EMAIL)) {
      return 'email';
    }

    // Validate phone number based on the North American Numbering Plan
    // https://en.wikipedia.org/wiki/North_American_Numbering_Plan
    $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
    if (preg_match( $regex, $address)) {
      return 'sms';
    }

    // To be better defined based on target OTT conditions
    /*
    if (isset($address)) {
      return 'ott';
    }
    */

    return false;
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
