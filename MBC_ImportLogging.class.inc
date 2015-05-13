<?PHP

use DoSomething\MBStatTracker\StatHat;

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
   * Constructor for MBC_TransactionalEmail
   *
   * @param array $settings
   *   Settings from external services - StatHat
   */
  public function __construct($messageBroker, $settings) {

    $this->messageBroker = $messageBroker;
    $this->settings = $settings;

    // Stathat
    $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-import-logging:');
    $this->statHat->setIsProduction(TRUE);
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

    if (isset($endpoint)) {
      $loggingApiUrl = getenv('DS_LOGGING_API_HOST') . ':' . getenv('DS_LOGGING_API_PORT') . '/api/v1' . $endpoint . '?' . http_build_query($cURLparameters);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $loggingApiUrl);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);

      // Only ack messages that the API has responded to
      if (is_string($result)) {
        $this->messageBroker->sendAck($payload);
      }

      echo '------- MBC_ImportLogging - updateLoggingAPI() END #' . $payload->delivery_info['delivery_tag'] . date('D M j G:i:s T Y') . ' -------', "\n";
    }
    else {
      echo 'Error - endpoint not defined for call to mb-logging-api.', "\n";
      print_r($payloadDetails);
    }

  }

}