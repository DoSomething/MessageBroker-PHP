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

  const LOGGING_API = '/api/v1';

  /**
   * Connection credentials for RabbitMQ.
   */
  private $credentials;

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

    $this->credentials = $credentials;
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
   *
   * @param interger $offset
   *   The time in seconds from the current time to start the logging activity
   *   query. This is the time value that the query will be repeated in the form
   *   of a cron task.
   * @param integer $interval
   *   The amount of time in seconds from the $offset value.
   *
   * Typically these values will be the same.
   */
  public function processLoggedEvents($offset, $interval) {

    echo '------- MBC_LoggingProcessor - processLoggedEvents() START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

    // Gather vote activity log entries for AGG every "$interval" seconds offset by "$offset" seconds.
    $voteActivities = $this->gatherActivities('vote', 'AGG', $offset, $interval);

    if ($voteActivities != FALSE) {
      $this->createTransactionals($voteActivities);
      $this->createMailChimpUsers($voteActivities);
      $this->assignMailChimpGroup($voteActivities);
    }

    echo '------- MBC_LoggingProcessor - processLoggedEvents() END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
  }

  /**
   * gatherActivitie: Cron job triggers gathering log entries to produce transactional events.
   *
   * @param string $activity
   *   The type of logged activity to be gathered.
   * @param string $source
   *   The name of source / site that the activity took place on.
   * @param integer $offset
   *   The time (in seconds) from the current time to start to query of the logged events.
   * @param integer $interval
   *   The time (in seconds) to end the query from the $offset time.
   */
  private function gatherActivities($activity, $source, $offset = NULL, $interval = NULL) {

    $curlUrl = $this->settings['mb_logging_api_host'];
    $port = $this->settings['mb_logging_api_port'];
    if ($port != 0 && is_numeric($port)) {
      $curlUrl .= ':' . (int) $port;
    }

    $params['type'] = $type;
    $params['source'] = $source;
    if (isset($offset)) {
      $params['offset'] = $offset;
    }
    if (isset($interval)) {
      $params['interval'] = $interval;
    }

    $loggingApiUrl = $curlUrl . self::LOGGING_API . '/user/activity?' . http_build_query($params);
    $result = $this->toolbox->curlGET($loggingApiUrl);

    if ($result[1] == 200) {
      $voteActivities = $result[0];
    }
    else {
      echo '- gatherActivities(): No results returned.', PHP_EOL;
      $voteActivities = FALSE;
    }

    return $voteActivities;
  }

  /**
   * createTransactionals: Submit messages to the transactionalQueue to trigger transactional
   * email messages.
   *
   * @param array $voteActivities
   *   A list of all logged activities to generate email list from.
   */
  private function createTransactionals($voteActivities) {

    $this->config['routingKey'] = 'agg.weeklydo.transactional';
    $mb = new MessageBroker($this->credentials, $this->config);

    foreach($voteActivities as $voteActivity) {
      $payload = $voteActivity['activity_details'];
      $voteActivity['email_template'] = 'agg2015-weekly-do-global';
      $voteActivity['email_tags'] = array('agg', 'weekly-do-global');
      $mb->publishMessage($payload);
    }

    $this->statHat->ezCount('mbp-logging-processor: MBC_LoggingProcessor: createTransactionals() - Weekly Do Internationl', 1);
    echo $messageCount . ' transactional email sent.', PHP_EOL;
  }

}
