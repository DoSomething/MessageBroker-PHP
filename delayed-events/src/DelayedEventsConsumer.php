<?php
/**
 * DelayedEventsConsumer
 */

namespace DoSomething\DelayedEvents;

use \SimpleXMLElement;
use \Exception;

use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\StatHat\Client as StatHat;

class DelayedEventsConsumer extends MB_Toolbox_BaseConsumer
{

  const TEXT_QUEUE_NAME = 'dispatchDelayedTextsQueue';
  const SIGNUP_MESSAGE_TYPE = 'scheduled_relative_to_signup_date';
  const REPORTBACK_MESSAGE_TYPE = 'scheduled_relative_to_reportback_date';

  /**
   * Gambit campaigns cache.
   *
   * @var array
   */
  private $gambitCampaignsCache = [];

  /**
   * Gambit campaign.
   *
   * @var boolean|string
   */
  private $gambit = false;

  /**
   * Preprocessed data.
   *
   * @var array
   */
  private $preprocessedData = [];


  /**
   * Constructor compatible with MBC_BaseConsumer.
   */
  public function __construct($targetMBconfig = 'messageBroker') {
    parent::__construct($targetMBconfig);

    // Cache gambit campaigns,
    $this->gambit = $this->mbConfig->getProperty('gambit');
    $gambitCampaigns = $this->gambit->getAllCampaigns();

    foreach ($gambitCampaigns as $campaign) {
      if ($campaign->campaignbot === true) {
        $this->gambitCampaignsCache[$campaign->id] = $campaign;
      }
    }

    if (count($this->gambitCampaignsCache) < 1) {
      // Basically, die.
      throw new Exception('No gambit connection.');
    }
  }

  /**
   * Initial method triggered by blocked call in mbc-registration-mobile.php.
   *
   * @param array $messages
   *   The contents of the queue entry message being processed.
   */
  public function consumeDelayedEvents($messages) {
    echo '------ delayed-events-consumer - DelayedEventsConsumer->consumeDelayedEvents() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;
    $this->preprocessedData = [];
    $this->gambitCampaign = false;

    try {

      $processData = [];

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
          $this->statHat->ezCount('MB_Toolbox: MB_Toolbox_BaseConsumer: consumeQueue Exception', 1);

          unset($messages[$key]);
          $this->messageBroker->sendNack($message, false, false);
          continue;
        }

        // Check that message is qualified for this consumer.
        if (!$this->canProcess($payload)) {
          echo '- canProcess() is not passed, removing from queue:' . $body . PHP_EOL;
          $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: skipping', 1);

          unset($messages[$key]);
          $this->messageBroker->sendNack($message, false, false);
          continue;
        }

        $this->statHat->ezCount('MB_Toolbox: MB_Toolbox_BaseConsumer: consumeQueue', 1);

        // Preprocess data.
        $this->setter([$message, $payload]);

      }

      if (!$this->preprocessedData) {
        echo '- consumeDelayedEvents() no data to process.' . PHP_EOL;
        return;
      }

      // Process data.
      $this->process($this->preprocessedData);
      $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: process', count($this->preprocessedData));
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
        $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: Exception: Connection timed out', 1);
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      }
      elseif (!(strpos($e->getMessage(), 'Operation timed out') === false)) {
        echo '** Operation timed out... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: Exception: Operation timed out', 1);
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      }
      elseif (!(strpos($e->getMessage(), 'Failed to connect') === false)) {
        echo '** Failed to connect... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
        $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: Exception: Failed to connect', 1);
      }
      elseif (!(strpos($e->getMessage(), 'Bad response - HTTP Code:500') === false)) {
        echo '** Connection error, http code 500... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
        $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: Exception: Bad response - HTTP Code:500', 1);
      }
      else {
        echo '- Not timeout or connection error, message to deadLetterQueue: ' . date('j D M Y G:i:s T'), PHP_EOL;
        echo '- Error message: ' . $e->getMessage(), PHP_EOL;

        // Uknown exception, save the message to deadLetter queue.
        $this->statHat->ezCount('delayed-events-consumer: DelayedEventsConsumer: Exception: deadLetter', 1);
        parent::deadLetter($this->message, 'DelayedEventsConsumer->consumeDelayedEvents() Error', $e);

        // Send Negative Acknowledgment, don't requeue the message.
        $this->messageBroker->sendNack($this->message['payload'], false, false);
      }
    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueStatus = parent::queueStatus(self::TEXT_QUEUE_NAME);

    echo  PHP_EOL . '------ delayed-events-consumer - DelayedEventsConsumer->consumeDelayedEvents() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
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
    // Check mobile number presence.
    if (empty($payload['mobile'])) {
      echo '** canProcess(): mobile number was not defined, skipping.' . PHP_EOL;

      return false;
    }

    // Check application id.
    if (empty($payload['application_id'])) {
      echo '** canProcess(): application_id not set.' . PHP_EOL;

      return false;
    }

    // Check that application id is allowed.
    $supportedApps = ['US', 'MUI'];
    if (!in_array($payload['application_id'], $supportedApps)) {
      echo '** canProcess(): Unsupported application: '
        . $payload['application_id'] . '.' . PHP_EOL;

      return false;
    }


    // Check activity presence.
    if (empty($payload['activity'])) {
      echo '** canProcess(): activity not set.' . PHP_EOL;
      parent::reportErrorPayload();

      return false;
    }

    // Check that the activity is allowed.
    $allowedActivities = [
      'campaign_signup',
      'campaign_reportback',
    ];
    if (!in_array($payload['activity'], $allowedActivities)) {
      echo '** canProcess(): activity is not supported: '
        . $payload['activity'] . '.' . PHP_EOL;

      return false;
    }

    // Check campaign id presence.
    if (empty($payload['event_id'])) {
      echo '** canProcess(): campaign id is nor provided.' . PHP_EOL;
      parent::reportErrorPayload();

      return false;
    }

    // Check that campaign is enabled on Gambit.
    $campaignId = (int) $payload['event_id'];

    // Only if enabled on Gambit.
    if (!array_key_exists($campaignId, $this->gambitCampaignsCache)) {
      echo '** canProcess(): Campaign is not available on Gambit: '
        . $campaignId . ', skipping.' . PHP_EOL;

      return false;
    }

    $this->gambitCampaign = $this->gambitCampaignsCache[$campaignId];
    if (empty($this->gambitCampaign)) {
      echo '** canProcess(): Campaign is not enabled on Campaignbot: '
        . $campaignId . ', ignoring.' . PHP_EOL;
      parent::reportErrorPayload();

      return false;
    }

    echo '** canProcess(): passed for ' . $payload['mobile']
      . ' event ' . $payload['activity']
      . ' campaign id ' . $payload['event_id'] . PHP_EOL;
    return true;
  }

  /**
   * Data processing logic.
   */
  protected function setter($arguments) {
    // Damn you, bad OOP design.
    list($message, $payload) = $arguments;

    // 1. Index by user mobile.
    $phone = $payload['mobile'];
    $dataItem = &$this->preprocessedData[$phone];

    // 2. Index by message type.
    $messageType = false;
    switch ($payload['activity']) {

      case 'campaign_signup':
        $messageType = self::SIGNUP_MESSAGE_TYPE;
        break;

      case 'campaign_reportback':
        $messageType = self::REPORTBACK_MESSAGE_TYPE;
        break;

      default:
        throw new Exception('This should never be called.');
        break;

    }
    $dataItem[$messageType] = [];

    // 3. Index by campaign id and prepare Gambit request arguments.
    $campaignId = $payload['event_id'];
    $dataItem[$messageType][$campaignId] = $message;

    // Done. The priority will be determined after all data is processed.
    return true;
  }

  /**
   * Forwards results to gambit.
   */
  protected function process($preprocessedData) {
    foreach ($preprocessedData as $phone => $messageTypes) {

      // 1. Prioritize message types.
      // 1.1 Check if there is an event of signup message type.
      $campaignId = false;
      $messageType = false;
      if (!empty($messageTypes[self::SIGNUP_MESSAGE_TYPE])) {
        $messageType = self::SIGNUP_MESSAGE_TYPE;
        $campaignId = $this->pickCampaign($messageTypes[$messageType]);
      }

      // 1.2. If there's not, check if there's a reportback event.
      if (empty($campaignId) && !empty($messageTypes[self::REPORTBACK_MESSAGE_TYPE])) {
        $messageType = self::REPORTBACK_MESSAGE_TYPE;
        $campaignId = $this->pickCampaign($messageTypes[$messageType]);
      }

      // 1.3. If there's still no data, that's an unexpected error.
      if (empty($campaignId)) {
        if (empty($messageType)) {
          // Unknown message type.
          throw new Exception('Integrity violation: inconsistent message types '
            . json_encode(array_keys($gambitCampaignIds)));
        }
        // Unknown campaign.
        throw new Exception('Integrity violation: can\'t cross-reference'
         . ' event campaigns ' . json_encode($campaigns)
          . ' and gambit cache' . json_encode($gambitCampaignIds));
        continue;
      }

      echo '** process(): Sending message to '. $phone . ' type ' . $messageType
        . ' campaign ' . $campaignId . PHP_EOL;

      $message = $messageTypes[$messageType][$campaignId];
      try {
        // Send the message.
        $this->gambit->createCampaignMessage($campaignId, $phone, $messageType);
        echo '*** Success!' . PHP_EOL;
        $this->ackAll($messageTypes);
      } catch (Exception $e) {
        echo '*** Gambit error: ' . $e->getMessage() . PHP_EOL;
        $deadLetter = $messageTypes;
        $deadLetter['original'] = $message;
        parent::deadLetter($deadLetter, 'MessagingGroupsConsumer->process()', $e);
        $this->nackAll($messageTypes);
      }
    }
  }

  /**
   * Returns first campaign from the input array based on Gambit cache order.
   *
   * @param  array
   *   Array of campaigns indexed by campaign id
   *
   * @return string|false
   *   Campaign id or false
   */
  private function pickCampaign($campaigns) {
    static $gambitCampaignIds = null;
    if ($gambitCampaignIds === null) {
       $gambitCampaignIds = array_keys($this->gambitCampaignsCache);
    }

    foreach ($gambitCampaignIds as $gambitCampaignId) {
      if (!empty($campaigns[$gambitCampaignId])) {
        // Select first campaign found in Gambit cache.
        return $gambitCampaignId;
      }
    }
    return false;
  }

  private function ackAll($messageTypes) {
    $this->resolveAll($messageTypes, 'ack');
  }

  private function nackAll($messageTypes) {
    $this->resolveAll($messageTypes, 'nack');
  }

  private function resolveAll($messageTypes, $action) {
    foreach ($messageTypes as $messageType) {
      foreach ($messageType as $message) {
        if ($action == 'ack') {
          $this->messageBroker->sendAck($message);
        } else {
          $this->messageBroker->sendNack($message, false, false);
        }
      }
    }
  }

}
