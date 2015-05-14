<?PHP

use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/**
 * MBC_ImportLogging class - functionality related to the Message Broker
 * producer mbc-import-logging.
 */
class MBC_ImportLogging
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

    // Stathat
    $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-import-logging:');
    $this->statHat->setIsProduction(isset($settings['use_stathat_tracking']) ? $settings['use_stathat_tracking'] : FALSE);
  }

  /**
   * Submit user campaign activity to the UserAPI
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function updateLoggingAPI($payload) {

    echo '------- MBC_ImportLogging - updateLoggingAPI() START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $payloadDetails = unserialize($payload->body);

    $post = array(
      'logging_timestamp' => isset($payloadDetails['log-timestamp']) ? $payloadDetails['log-timestamp'] : $payloadDetails['logged']
    );

    // User import summary logging - track each batch
    if (isset($payloadDetails['log-type']) && $payloadDetails['log-type'] == 'file-import') {

      $endpoint = '/imports/summaries';
      $cURLparameters['type'] = 'user_import';
      $cURLparameters['source'] = $payloadDetails['source'];

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

    }
    // Log user import existing details
    elseif (isset($payloadDetails['mobile']) || isset($payloadDetails['email']) || isset($payloadDetails['drupal-uid'])) {

      $endpoint = '/imports';
      $cURLparameters['type'] = 'user_import';
      $cURLparameters['exists'] = 1;
      $cURLparameters['source'] = isset($payloadDetails['source']) ? $payloadDetails['source'] : 'niche';
      $cURLparameters['origin'] = $payloadDetails['origin']['name'] ;
      $cURLparameters['processed_timestamp'] = $payloadDetails['origin']['processed'];

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
        $post['email_acquired'] = $payloadDetails['email-acquired'];
      }
      if (isset($payloadDetails['drupal-uid']) && $payloadDetails['drupal-uid'] != NULL) {
        if (isset($payloadDetails['email'])) {
          $post['drupal_email'] = $payloadDetails['email'];
        }
        $post['drupal_uid'] = $payloadDetails['drupal-uid'];
      }

    }
    else {
      echo '- ERROR - Payload missing key values: ' . print_r($payloadDetails, TRUE), PHP_EOL;
      $endpoint = NULL;
      $this->messageBroker->sendAck($payload);
    }

    if (isset($endpoint)) {
      $loggingApiUrl = getenv('DS_LOGGING_API_HOST') . ':' . getenv('DS_LOGGING_API_PORT') . '/api/v1' . $endpoint . '?' . http_build_query($cURLparameters);
      $result = $this->toolbox->curlPOST($loggingApiUrl, $post);

      // Only ack messages that the API has responded as "created" (201).
      if ($result[1] == 201) {
        $this->messageBroker->sendAck($payload);
      }

      echo '------- MBC_ImportLogging - updateLoggingAPI() END #' . $payload->delivery_info['delivery_tag'] . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
    }

  }

}