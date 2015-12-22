<?PHP
/**
 * MBC_ImportLogging: Class to support logging user import activity.
 */

namespace DoSomething\MBC_LoggingGateway;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_cURL;

/**
 * MBC_LoggingGateway class - functionality related to the Message Broker
 * consumer mbc-logging-gateway.
 */
class MBC_LoggingGateway extends MB_Toolbox_BaseConsumer
{

  /**
   * Message Broker Toolbox cURL utilities.
   *
   * @var object $mbToolboxCURL
   */
  private $mbToolboxCURL;

  /**
   *
   *
   * @var object $endPoint
   */
  private $endPoint;

  /**
   *
   *
   * @var object $cURLparameters
   */
  private $cURLparameters;

  /**
   *
   *
   * @var object $post
   */
  private $post;

  /**
   * Constructor for MBC_LoggingGateway
   */
  public function __construct() {

    parent::__construct();
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
  }

  /**
   * Triggered when loggingGatewayQueue contains a message. Delegate message
   * processing to class specific to the logging message type.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeLoggingGatewayQueue($payload) {

    echo '------- MBC_LoggingGateway - consumeQueue() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    parent::consumeQueue($payload);
    $this->logConsumption('email');

    if ($this->canProcess()) {

      try {

        $this->setter($this->message);
        $this->process();
      }
      catch(Exception $e) {
        echo 'Error sending logging request to mb-logging-api. Error: ' . $e->getMessage();

        // @todo: Send copy of message to "dead message queue" with details of the original processing: date,
        // origin queue, processing app. The "dead messages" queue can be used to monitor health.
      }

    }
    else {
      echo '- ' . $this->message['log-type'] . ' can\'t be processed, holding in queue.', PHP_EOL;

      // @todo: Send copy of message to "dead message queue" with details of the original processing: date,
      // origin queue, processing app. The "dead messages" queue can be used to monitor health.

    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueMessages = parent::queueStatus('transactionalQueue');
    echo '- queueMessages ready: ' . $queueMessages['ready'], PHP_EOL;
    echo '- queueMessages unacked: ' . $queueMessages['unacked'], PHP_EOL;

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
        if (!(isset($this->message['signup_count']))) {
          echo '- canProcess() ERROR: file-import signup_count not set.', PHP_EOL;
          return FALSE;
        }
        if (!(isset($this->message['skipped']))) {
          echo '- canProcess() ERROR: file-import skipped not set.', PHP_EOL;
          return FALSE;
        }
        break;

      case 'user-import-niche':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':

        break;

      case 'vote':

        break;

      case 'transactional':

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

   switch ($payloadDetails['log-type']) {

      case 'file-import':
        list($endPoint, $cURLparameters, $post) = $this->logUserImportFile($this->payloadDetails);

        break;

      case 'user-import-niche':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':
        list($endPoint, $cURLparameters, $post) = $this->logImportExistingUser($this->payloadDetails);
        break;

      case 'vote':
        list($endPoint, $cURLparameters, $post) = $this->logActivity($this->payloadDetails);
        break;

      case 'transactional':
        list($endPoint, $cURLparameters, $post) = $this->logTransactional($this->payloadDetails);

    }

    $this->endPoint = $endPoint;
    $this->cURLparameters = $cURLparameters;
    $this->post = $post;
  }

  /**
   * process(): Gather message settings into submission to mb-logging-api
   */
  protected function process() {

    $loggingApiUrl = $this->settings['mb_logging_api_host'] . ':' . $this->settings['mb_logging_api_port'] . '/api/v1' . $endPoint . '?' . http_build_query($cURLparameters);
    $result = $this->toolbox->curlPOST($loggingApiUrl, $post);

    // Only ack messages that the API has responded as "created" (201).
    if ($result[1] == 201) {
      // $this->statHat->ezCount('mbc-logging-gateway: submitLogEntry()', 1);
      $this->messageBroker->sendAck($payload);
    }
    else {
      echo '- ERROR, MBC_LoggingGateway->submitLogEntry(): Failed to POST to ' . $loggingApiUrl, PHP_EOL;
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
    $cURLparameters['source'] = isset($payloadDetails['source']) ? $payloadDetails['source'] : 'niche';
    $cURLparameters['origin'] = $payloadDetails['origin']['name'] ;
    $cURLparameters['processed_timestamp'] = $payloadDetails['origin']['processed'];

    $post = array();
    if (isset($payloadDetails['origin'])) {
      $post['origin'] = array(
        'name' => $payloadDetails['origin']['name'],
        'processed_timestamp' => $payloadDetails['origin']['processed']
      );
    }
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
    $post['message'] = serialize($payloadDetails);

    if (isset($payloadDetails['mobile'])) {
      $post['mobile'] = $payloadDetails['mobile'];
    }

    return array($endpoint, $cURLparameters, $post);
  }

}
