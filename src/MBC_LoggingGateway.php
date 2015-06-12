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
   * Triggered when loggingGatewayQueue contains a message. Deligate message
   * processing to class specific to the logging message type.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeQueue($payload) {

    echo '------- MBC_LoggingGateway - consumeQueue() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    $payloadDetails = unserialize($payload->body);

    switch ($payloadDetails['log-type']) {

      case 'file-import':
        list($endPoint, $post) = $this->logUserImportFile($payloadDetails);

        break;

      case 'user-import-niche':
      case 'user-import-att-ichannel':
      case 'user-import-hercampus':
      case 'user-import-teenlife':
        list($endPoint, $post) = $this->logImportExistingUser($payloadDetails);

        break;

      case 'vote':
        list($endPoint, $post) = $this->logVote($payloadDetails);

        break;

      default:

    }

    $this->submitLogEntry($endPoint, $post);

    echo '------- MBC_LoggingGateway - consumeQueue() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

  }

  /**
   *
   * @param array $payloadDetails
   *
   */
  private function logUserImportFile($payloadDetails) {

  }

  /**
   *
   * @param array $payloadDetails
   *
   */
  private function logImportExistingUser($payloadDetails) {

  }

  /**
   *
   * @param array $payloadDetails
   *
   */
  private function logVote($payloadDetails) {

  }

}
