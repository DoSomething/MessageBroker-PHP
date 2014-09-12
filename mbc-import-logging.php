<?php
/**
 * mbc-user-campaign.php
 *
 * Collect user campaign activity from the userCampaignActivityQueue. Update the
 * UserAPI / database with user activity.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

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
 //   $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-import-logging:');
 //   $this->statHat->setIsProduction(FALSE);
  }

  /**
   * Submit user campaign activity to the UserAPI
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function updateLoggingAPI($payload) {
    
    echo '------- MBC_ImportLogging - updateLoggingAPI() START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";

    $payloadDetails = unserialize($payload->body);
    
    $post = array(
      'logging_timestamp' => isset($payloadDetails['log-timestamp']) ? $payloadDetails['log-timestamp'] : $payloadDetails['logged']
    );
    
    if (isset($payloadDetails['log-type']) && $payloadDetails['log-type'] == 'file-import') {

      $endpoint = '/userimport/niche/summary';
      if (isset($payloadDetails['target-CSV-file']) && $payloadDetails['target-CSV-file'] != NULL) {
        $post['target_CSV_file'] = $payloadDetails['target-CSV-file'];
      }
      if (isset($payloadDetails['signup-count']) && $payloadDetails['signup-count'] != NULL) {
        $post['signup_count'] = $payloadDetails['signup-count'];
      }
      if (isset($payloadDetails['skipped']) && $payloadDetails['skipped'] != NULL) {
        $post['skipped'] = $payloadDetails['skipped'];
      }

    }
    elseif (isset($payloadDetails['mobile']) || isset($payloadDetails['email']) || isset($payloadDetails['drupal_uid'])) {

      $endpoint = '/userimport/existing/niche';
      if (isset($payloadDetails['mobile']) && $payloadDetails['mobile'] != NULL) {
        $post['phone'] = $payloadDetails['mobile'];
        $post['phone_status'] = $payloadDetails['mobile-error'];
      }
      if (isset($payloadDetails['email']) && $payloadDetails['email'] != NULL) {
        $post['email'] = $payloadDetails['email'];
        $post['email_status'] = $payloadDetails['email-status'];
        $post['email_acquired_timestamp'] = $payloadDetails['mobile-error'];
      }
      if (isset($payloadDetails['drupal-uid']) && $payloadDetails['drupal-uid'] != NULL) {
        $post['drupal_email'] = $payloadDetails['email'];
        $post['drupal_uid'] = $payloadDetails['drupal-uid'];
      }

    }

    if (isset($endpoint)) {
      $loggingApiUrl = getenv('DS_IMPORT_LOGGING_API_HOST') . ':' . getenv('DS_IMPORT_LOGGING_API_PORT') . '/api' . $endpoint;
  
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $loggingApiUrl);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $result = curl_exec($ch);
      curl_close($ch);
      
      echo $loggingApiUrl, "\n";
      echo print_r($post, TRUE), "\n";
      echo print_r($result, TRUE), "\n\n";
      
      // Only ack messages that the API has responded to
      if (is_string($result)) {
        $this->messageBroker->sendAck($payload);
      }
  
      echo '------- MBC_ImportLogging - updateLoggingAPI() END #' . $payload->delivery_info['delivery_tag'] . date('D M j G:i:s T Y') . ' -------', "\n";
    }
    else {
      echo 'Error - endpoint not defined for call to mb-logging-api.', "\n";
    }

  }

}

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$config = array(
  'exchange' => array(
    'name' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE"),
    'type' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_TYPE"),
    'passive' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_USER_IMPORT_LOGGING_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    'user_import' => array(
      'name' => getenv("MB_USER_IMPORT_LOGGING_QUEUE"),
      'passive' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_PASSIVE"),
      'durable' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_USER_IMPORT_LOGGING_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
  'consume' => array(
    'consumer_tag' => getenv("MB_USER_IMPORT_LOGGING_CONSUME_TAG"),
    'no_local' => getenv("MB_USER_IMPORT_LOGGING_CONSUME_NO_LOCAL"),
    'no_ack' => getenv("MB_USER_IMPORT_LOGGING_CONSUME_NO_ACK"),
    'exclusive' => getenv("MB_USER_IMPORT_LOGGING_CONSUME_EXCLUSIVE"),
    'nowait' => getenv("MB_USER_IMPORT_LOGGING_CONSUME_NOWAIT"),
  ),
  'routingKey' => getenv("MB_USER_IMPORT_LOGGING_ROUTING_KEY"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
);

echo '------- mbc-impoert-logging START - ' . date('D M j G:i:s T Y') . ' -------', "\n";

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ImportLogging($mb, $settings), 'updateLoggingAPI'));


echo '------- mbc-impoert-logging END - ' . date('D M j G:i:s T Y') . ' -------', "\n";
