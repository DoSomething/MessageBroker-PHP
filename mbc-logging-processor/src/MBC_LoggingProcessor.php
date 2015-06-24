<?PHP
/**
 * MBC_LoggingProcessor: Class to process log entries to determine if transactional
 * events should be triggered.
 */

namespace DoSomething\MBC_LoggingProcessor;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/**
 * MBC_LoggingGateway class - functionality related to the Message Broker
 * consumer mbc-logging-gateway.
 */
class MBC_LoggingProcessor
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
   * Setting Rabbit configration.
   *
   * @var array
   */
  private $config;

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
  public function __construct($credentials, $config, $settings) {

    $this->messageBroker = new MessageBroker($credentials, $config);;
    $this->settings = $settings;
    $this->config = $config;

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);
  }

  /**
   * Cron job triggers gathering log entries to produce transactional events.
   */
  public function processLoggedEvents() {

    echo '------- MBC_LoggingProcessor - processLoggedEvents() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;


    echo '------- MBC_LoggingProcessor - processLoggedEvents() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

}
