<?php
/**
 * mbc-import-logging.php
 *
 * Collect user import activity from the userImportExistingLoggingQueue. Update
 * the LoggingAPI / database with import activity via mb-logging.
 */

date_default_timezone_set('America/New_York');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBStatTracker\StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/messagebroker-config/mb-secure-config.inc';
require_once __DIR__ . '/mb-config.inc';

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


// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);

$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
);

$config = array();
$source = __DIR__ . '/messagebroker-config/mb_config.json';
$mb_config = new MB_Configuration($source, $settings);
$userImportExistingLogging = $mb_config->exchangeSettings('directUserImportExistingLogging');

$config['exchange'] = array(
  'name' => $userImportExistingLogging->name,
  'type' => $userImportExistingLogging->type,
  'passive' => $userImportExistingLogging->passive,
  'durable' => $userImportExistingLogging->durable,
  'auto_delete' => $userImportExistingLogging->auto_delete,
);
$config['queue'][] = array(
  'name' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->name,
  'passive' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->passive,
  'durable' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->durable,
  'exclusive' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->exclusive,
  'auto_delete' =>  $userImportExistingLogging->queues->userImportExistingLoggingQueue->auto_delete,
  'bindingKey' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->binding_key,
);
$config['routing_key'] = $userImportExistingLogging->queues->userImportExistingLoggingQueue->routing_key;
$config['consume'] = array(
  'no_local' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->no_local,
  'no_ack' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->no_ack,
  'nowait' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->nowait,
  'exclusive' => $userImportExistingLogging->queues->userImportExistingLoggingQueue->consume->exclusive,
);


$bla = FALSE;
if ($bla) {
  $bla = TRUE;
}


echo '------- mbc-impoert-logging START - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_ImportLogging($mb, $settings), 'updateLoggingAPI'));


echo '------- mbc-impoert-logging END - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
