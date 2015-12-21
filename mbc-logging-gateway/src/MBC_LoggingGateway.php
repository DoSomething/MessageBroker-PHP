<?PHP
/**
 * MBC_ImportLogging: Class to support logging user import activity.
 */

namespace DoSomething\MBC_LoggingGateway;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/**
 * MBC_LoggingGateway class - functionality related to the Message Broker
 * consumer mbc-logging-gateway.
 */
class MBC_LoggingGateway
{

  /**
   * Message Broker connection to RabbitMQ
   */
  private $messageBroker;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $settings;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * Setting from external services - Message Broker Toolbox.
   *
   * @var object
   */
  private $toolbox;

  /**
   * Constructor for MBC_TransactionalEmail
   *
   * @param array $settings
   *   Settings from external services - StatHat
   */
  public function __construct($messageBroker, $settings) {
    $this->messageBroker = $messageBroker;
    $this->settings = $settings;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);
  }

  /**
   * Triggered when loggingGatewayQueue contains a message. Delegate message
   * processing to class specific to the logging message type.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeQueue($payload) {

    echo '------- MBC_LoggingGateway - consumeQueue() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $endPoint = NULL;
    $cURLparameters = NULL;
    $post = NULL;

    $payloadDetails = unserialize($payload->body);

    switch ($payloadDetails['log-type']) {

      case 'file-import':
        list($endPoint, $cURLparameters, $post) = $this->logUserImportFile($payloadDetails);

        break;

      case 'user-import-niche':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':
        list($endPoint, $cURLparameters, $post) = $this->logImportExistingUser($payloadDetails);

        break;

      case 'vote':
        list($endPoint, $cURLparameters, $post) = $this->logActivity($payloadDetails);

        break;

      case 'transactional':
        list($endPoint, $cURLparameters, $post) = $this->logTransactional($payloadDetails);

        break;

      default:
        echo '- ERROR - Payload missing log-type value: ' . print_r($payloadDetails, TRUE), PHP_EOL;
        $endpoint = NULL;
        $this->messageBroker->sendAck($payload);

    }

    if ($endPoint != NULL) {
      $this->submitLogEntry($endPoint, $cURLparameters, $post, $payload);
    }
    echo '------- MBC_LoggingGateway - consumeQueue() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
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
    if (isset($payloadDetails['target-CSV-file']) && $payloadDetails['target-CSV-file'] != NULL) {
      $post['target_CSV_file'] = $payloadDetails['target-CSV-file'];
    }
    if (isset($payloadDetails['signup-count']) && $payloadDetails['signup-count'] != NULL) {
      $post['signup_count'] = $payloadDetails['signup-count'];
    }
    if (isset($payloadDetails['skipped'])) {
      $post['skipped'] = $payloadDetails['skipped'];
    }

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

  /**
   * submitLogEntry: Submit log entries to mb-logging-api.
   *
   * @param string $endPoint
   *   The path on the mb-logging-api to submit the log entry.
   * @param array $cURLparameters
   *   Parameters in the POST path for the mb-logging-api to use to define the type
   *   of log entry.
   * @param array $post
   *   Collection of values to submit for logging entry.
   * @param array $payload
   *   Orginal message payload from Rabbit server. Used to ack messages that have been
   *   successully POSTed.
   */
  public function submitLogEntry($endPoint, $cURLparameters, $post, $payload) {

    $loggingApiUrl = $this->settings['mb_logging_api_host'] . ':' . $this->settings['mb_logging_api_port'] . '/api/v1' . $endPoint . '?' . http_build_query($cURLparameters);
    $result = $this->toolbox->curlPOST($loggingApiUrl, $post);

    // Only ack messages that the API has responded as "created" (201).
    if ($result[1] == 201) {
      $this->statHat->ezCount('mbc-logging-gateway: submitLogEntry()', 1);
      $this->messageBroker->sendAck($payload);
    }
    else {
      echo '- ERROR, MBC_LoggingGateway->submitLogEntry(): Failed to POST to ' . $loggingApiUrl, PHP_EOL;
      echo '  * Returned POST results: ' . print_r($result, TRUE), PHP_EOL;
      $this->statHat->ezCount('mbc-logging-gatewa: ERROR submitLogEntry()', 1);
    }

  }

}
