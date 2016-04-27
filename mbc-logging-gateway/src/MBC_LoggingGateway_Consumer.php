<?PHP
/**
 * MBC_ImportLogging: Class to support logging user import activity.
 */

namespace DoSomething\MBC_LoggingGateway;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use \Exception;

/**
 * MBC_LoggingGateway class - functionality related to the Message Broker
 * consumer mbc-logging-gateway.
 */
class MBC_LoggingGateway_Consumer extends MB_Toolbox_BaseConsumer
{
  const MB_LOGGING_API = '/api/v1';
  const RETRY_SECONDS = 20;

  /**
   * Message Broker Toolbox cURL utilities.
   *
   * @var object $mbToolboxCURL
   */
  private $mbToolboxCURL;

  /**
   * mb-logging-api configuration settings.
   *
   * @var array $mbLoggingAPIConfig
   */
  private $mbLoggingAPIConfig;

  /**
   * The mb-logging-api endpoint to submit logging entry to.
   *
   * @var string $endPoint
   */
  private $endPoint;

  /**
   * Parameters to be submitted with logging request.
   *
   * @var array $cURLparameters
   */
  private $cURLparameters;

  /**
   * POST values for submission to mb-logging-api andpoint POST request.
   *
   * @var array $post
   */
  private $post;

  /**
   * Constructor for MBC_LoggingGateway
   */
  public function __construct() {

    parent::__construct();
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $this->mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');
  }

  /**
   * Triggered when loggingGatewayQueue contains a message.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeLoggingGatewayQueue($payload) {

    echo '------- MBC_LoggingGateway - consumeQueue() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    parent::consumeQueue($payload);

    try {

      if ($this->canProcess()) {
        $this->logConsumption('log-type');
        $this->setter($this->message);
        $this->process();
      }
      else {
        echo '- ' . $this->message['log-type'] . ' can\'t be processed, sending to deadLetterQueue.', PHP_EOL;
        $this->statHat->ezCount('mbc-logging-gateway: MBC_LoggingGateway_Consumer: Exception: deadLetter', 1);
        parent::deadLetter($this->message, 'MBC_LoggingGateway_Consumer->consumeLoggingGatewayQueue() Error', $e->getMessage());

      }
    }

    catch(Exception $e) {
      echo 'Error sending logging request to mb-logging-api, retrying... Error: ' . $e->getMessage();
      sleep(self::RETRY_SECONDS);
      $this->messageBroker->sendNack($this->message['payload']);
      echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      $this->statHat->ezCount('mbc-logging-gateway: MBC_LoggingGateway_Consumer: Exception: Bad response - HTTP Code:500', 1);
    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueMessages = parent::queueStatus('loggingGatewayQueue');

    echo '------- MBC_LoggingGateway - consumeQueue() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

  /**
   * Conditions to test before processing the message.
   *
   * @return boolean
   */
  protected function canProcess() {

    if (!(isset($this->message['log-type']))) {
      echo '- canProcess() ERROR: log-type not set.', PHP_EOL;
      return FALSE;
    }

    $supportedLogTypes = [
      'file-import',
      'user-import-niche',
      'user-import-afterschool',
      'user-import-att-ichannel',
      'user-import-hercampus',
      'user-import-teenlife',
      'vote',
      'transactional',
    ];
    if (!(in_array($this->message['log-type'], $supportedLogTypes))) {
      echo '- canProcess() ERROR: Unsupported log-type: ' . $this->message['log-type'], PHP_EOL;
      return FALSE;
    }

    if (!(isset($this->message['source']))) {
      echo '- canProcess() ERROR: source not set.', PHP_EOL;
      return FALSE;
    }

    // log-type specific requirements
    switch ($this->message['log-type']) {

      case 'file-import':

        if (!(isset($this->message['target-CSV-file']))) {
          echo '- canProcess() ERROR: file-import target-CSV-file not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['signup-count']))) {
          echo '- canProcess() ERROR: file-import signup-count not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['skipped']))) {
          echo '- canProcess() ERROR: file-import skipped not set.', PHP_EOL;
          return FALSE;
        }
        break;

      case 'user-import-niche':
      case 'user-import-afterschool':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':

        if (!(isset($this->message['origin']['name']))) {
          echo '- canProcess() ERROR: [origin][name] not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['origin']['processed']))) {
          echo '- canProcess() ERROR: [origin][processed] not set.', PHP_EOL;
          return FALSE;
        }
        break;

      case 'vote':

        if (!(isset($this->message['email']))) {
          echo '- canProcess() vote ERROR: email not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['activity']))) {
          echo '- canProcess() vote ERROR: activity not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['activity_date']))) {
          echo '- canProcess() vote ERROR: activity_date not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['activity_timestamp']))) {
          echo '- canProcess() vote ERROR: activity_timestamp not set.', PHP_EOL;
          return FALSE;
        }
        break;

      case 'transactional':

        if (!(isset($this->message['email']))) {
          echo '- canProcess() transactional ERROR: email not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['activity_timestamp']))) {
          echo '- canProcess() transactional ERROR: activity_timestamp not set.', PHP_EOL;
          return FALSE;
        }
        break;

      default:

        throw new Exception('Unsupported log-type of type not set: ' . $this->message['log-type']);
        break;
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

   switch ($message['log-type']) {

      case 'file-import':
        list($endPoint, $cURLparameters, $post) = $this->logUserImportFile($message);

        break;

      case 'user-import-niche':
      case 'user-import-afterschool':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':
        list($endPoint, $cURLparameters, $post) = $this->logImportExistingUser($message);
        break;

      case 'vote':
        list($endPoint, $cURLparameters, $post) = $this->logActivity($message);
        break;

      case 'transactional':
        list($endPoint, $cURLparameters, $post) = $this->logTransactional($message);

    }

    $this->endPoint = $endPoint;
    $this->cURLparameters = $cURLparameters;
    $this->post = $post;
  }

  /**
   * process(): Gather message settings into submission to mb-logging-api
   */
  protected function process() {

    $loggingApiUrl  = $this->mbToolboxCURL->buildcURL($this->mbLoggingAPIConfig);
    $loggingApiUrl .=  self::MB_LOGGING_API . $this->endPoint . '?' . http_build_query($this->cURLparameters);
    $result = $this->mbToolboxCURL->curlPOST($loggingApiUrl, $this->post);

    // Only ack messages that the API has responded as "created" (201).
    if ($result[1] == 201) {
      // $this->statHat->ezCount('mbc-logging-gateway: submitLogEntry()', 1);
      $this->messageBroker->sendAck($this->message['payload']);
    }
    else {
      echo '- ERROR, MBC_LoggingGateway->process(): Failed to POST to ' . $loggingApiUrl, PHP_EOL;
      echo '  * Returned POST results: ' . print_r($result, TRUE), PHP_EOL;
      // $this->statHat->ezCount('mbc-logging-gatewa: ERROR submitLogEntry()', 1);
    }
  }

  /**
   * logUserImportFile: Format values for "file-import" log entry.
   *
   * @param array $payloadDetails
   *   Values submitted in activity message to be processed to create "file-import" log entry.
   *
   * @return string $endpoint
   *   The cURUL POST URL to mb-logging-api.
   * @return array $cURLparameters
   *   The parameters to include in the cURL POST.
   * @return array $post
   *   Post values for the cURL POST.
   */
  public function logUserImportFile($payloadDetails) {

    $endpoint = '/imports/summaries';
    $cURLparameters['type'] = 'user_import';
    $cURLparameters['source'] = $payloadDetails['source'];

    $post = array();
    $post['source'] = $payloadDetails['source'];
    $post['target_CSV_file'] = $payloadDetails['target-CSV-file'];
    $post['signup_count'] = $payloadDetails['signup-count'];
    $post['skipped'] = $payloadDetails['skipped'];

    return array($endpoint, $cURLparameters, $post);
  }

  /**
   * logImportExistingUser: Format values for "user-import-xxx" log entry.
   *
   * @param array $payloadDetails
   *   Values submitted in activity message to be processed to create "file-import" log entry.
   *
   * @return string $endpoint
   *   The cURUL POST URL to mb-logging-api.
   * @return array $cURLparameters
   *   The parameters to include in the cURL POST.
   * @return array $post
   *   Post values for the cURL POST.
   */
  public function logImportExistingUser($payloadDetails) {

    $endpoint = '/imports';
    $cURLparameters['type'] = 'user_import';
    $cURLparameters['exists'] = 1;
    $cURLparameters['source'] = $payloadDetails['source'];
    $cURLparameters['origin'] = $payloadDetails['origin']['name'] ;
    $cURLparameters['processed_timestamp'] = $payloadDetails['origin']['processed'];

    $post = array();
    $post['origin'] = array(
      'name' => $payloadDetails['origin']['name'],
      'processed_timestamp' => $payloadDetails['origin']['processed']
    );
    if (isset($payloadDetails['mobile']) && $payloadDetails['mobile'] != NULL) {
      $post['phone'] = $payloadDetails['mobile'];
      $post['phone_status'] = $payloadDetails['mobile-error'];
      $post['phone_acquired'] = $payloadDetails['mobile-acquired'];
    }
    if (isset($payloadDetails['email']) && $payloadDetails['email'] != NULL) {
      $post['email'] = $payloadDetails['email'];
      $post['email_status'] = $payloadDetails['email-status'];
      if (isset($payloadDetails['email-acquired'])) {
        $post['email_acquired'] = $payloadDetails['email-acquired'];
      }
    }
    if (isset($payloadDetails['drupal-uid']) && $payloadDetails['drupal-uid'] != NULL) {
      if (isset($payloadDetails['email'])) {
        $post['drupal_email'] = $payloadDetails['email'];
      }
      $post['drupal_uid'] = $payloadDetails['drupal-uid'];
    }

    return array($endpoint, $cURLparameters, $post);
  }

  /**
   * logActivity: Format values for "activity" log entry. A "catch" all
   * logging format that captures message payloads for future processing.
   *
   * @param array $payloadDetails
   *   Values submitted in activity message to be processed to create "vote" log entry.
   *
   * @return string $endpoint
   *   The cURUL POST URL to mb-logging-api.
   * @return array $cURLparameters
   *   The parameters to include in the cURL POST.
   * @return array $post
   *   Post values for the cURL POST.
   */
  public function logActivity($payloadDetails) {

    $endpoint = '/user/activity';
    $cURLparameters['type'] = $payloadDetails['activity'];

    $post = array();
    $post['email'] = $payloadDetails['email'];
    $post['activity'] = $payloadDetails['activity'];
    $post['source'] = $payloadDetails['source'];
    $post['activity_date'] = $payloadDetails['activity_date'];
    $post['activity_timestamp'] = $payloadDetails['activity_timestamp'];

    if (isset($payloadDetails['activity_details'])) {
      $post['activity_details'] = serialize($payloadDetails['activity_details']);
    }

    return array($endpoint, $cURLparameters, $post);
  }

  /**
   * logTransactional: Format values for "transactional" log entry.
   *
   * @param array $payloadDetails
   *   Values submitted in activity message to be processed to create "vote" log entry.
   *
   * @return string $endpoint
   *   The cURUL POST URL to mb-logging-api.
   * @return array $cURLparameters
   *   The parameters to include in the cURL POST.
   * @return array $post
   *   Post values for the cURL POST.
   */
  public function logTransactional($payloadDetails) {

    $endpoint = '/user/transactional';
    $cURLparameters['email'] = $payloadDetails['email'];
    $cURLparameters['activity'] = $payloadDetails['activity'];

    $post = array();
    $post['source'] = $payloadDetails['source'];
    $post['activity_timestamp'] = $payloadDetails['activity_timestamp'];
    $post['message'] = $payloadDetails['message'];

    if (isset($payloadDetails['mobile'])) {
      $post['mobile'] = $payloadDetails['mobile'];
    }

    return array($endpoint, $cURLparameters, $post);
  }

  /**
   * logConsumption(): Extend to log the status of processing a specific message
   * element as well as the message source.
   *
   * @param string $targetName
   */
  protected function logConsumption($targetName = NULL) {

    if ($targetName != NULL) {
      echo '** Consuming ' . $targetName . ': ' . $this->message[$targetName];
      if (isset($this->message['source'])) {
        echo ' from ' .  $this->message['source'], PHP_EOL;
      } else {
        echo ', source not defined.', PHP_EOL;
      }
    } else {
      echo '- logConsumption tagetName: "' . $targetName . '" not defined.', PHP_EOL;
    }
  }
}
