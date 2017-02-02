<?php
/*
 * UserStatus: ??
 */

namespace DoSomething\UserStatus;

use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\StatHat\Client as StatHat;

class UserStatus
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

    $this->toolbox = new MB_Toolbox($settings);
    $this->statHat = new StatHat([
      'ez_key' => $settings['stathat_ez_key'],
      'debug' => $settings['stathat_disable_tracking']
    ]);
  }

  /**
   * Initial method triggered by blocked call in mbc-UserStatus.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function userStatus($payload) {

    $payloadDetails = unserialize($payload->body);

    // Some processing of the message payload
    $post = array(
      'email' => '??',
    );

    // An example cURL POST to the Message Broker User API (mb-UserSpi) to store peristant
    // Message Broker relivant data using the MB_Toolbox library.
    $curlUrl = $this->settings['ds_user_api_host'];
    $port = $this->settings['ds_user_api_port'];
    if ($port != 0) {
      $curlUrl .= ":$port";
    }
    $curlUrl .= '/user';
    $result = $this->toolbox->curlPOST($curlUrl, $post);

    // Log consumer activity to StatHat for monitoring
    $this->statHat->clearAddedStatNames();
    if ($result[1] == 200) {
      $this->statHat->ezCount('mbc-UserStatus: success', 1);
    }
    else {
      echo '** FAILED to update ?? for email: ' . $post['email'], PHP_EOL;
      echo '------- mbc-UserStatus - UserStatus->UserStatus: $post: ' . print_r($post, TRUE) . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
      $this->statHat->ezCount('mbc-UserStatus: update failed', 1);
    }
  }

}
