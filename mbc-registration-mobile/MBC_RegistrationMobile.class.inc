<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_UserRegistration class - functionality related to the Message Broker
 * consumer mbc-registration-mobile.
 *
 * See:
 * - https://github.com/dosomething/mobilecommons-php
 * - https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#ProfileUpdate
 */
class MBC_RegistrationMobile
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
    $this->statHat = new StatHat($this->settings['stathat_ez_key'], 'mbc-registration-mobile:');
    $this->statHat->setIsProduction(isset($settings['use_stathat_tracking']) ? $settings['use_stathat_tracking'] : FALSE);
  }

  /**
   * Collect a batch of user registrations for submission to Mobile Commons from
   * the related RabbitMQ queue.
   *
   * @return array $payload
   *   Transactional queue entry details
   */
  public function consumeRegistrationMobileQueue($payload) {

    echo '------- MBC_RegistrationMobile START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;

    $this->statHat->clearAddedStatNames();
    $payloadDetails = unserialize($payload->body);
    $deliveryTag = $payload->delivery_info['delivery_tag'];

    if (!empty($payloadDetails['mobile']) &&
        !empty($payloadDetails['mc_opt_in_path_id'])) {

      $args = array(
        'phone_number' => $payloadDetails['mobile'],
        'opt_in_path_id' => $payloadDetails['mc_opt_in_path_id'],
      );

      // Optional profile details
      if (isset($payloadDetails['email'])) {
        $args['email'] = $payloadDetails['email'];
      }
      if (isset($payloadDetails['zip'])) {
        $args['postal_code'] = $payloadDetails['zip'];
      }
      if (isset($payloadDetails['merge_vars']['FNAME'])) {
        $args['first_name'] = $payloadDetails['merge_vars']['FNAME'];
      }
      elseif ($payloadDetails['first_name']) {
        $args['first_name'] = $payloadDetails['first_name'];
      }
      if (isset($payloadDetails['merge_vars']['LNAME'])) {
        $args['last_name'] = $payloadDetails['merge_vars']['LNAME'];
      }
      if (isset($payloadDetails['address1'])) {
        $args['street1'] = $payloadDetails['address1'];
      }
      if (isset($payloadDetails['address2'])) {
        $args['street2'] = $payloadDetails['address2'];
      }
      if (isset($payloadDetails['city'])) {
        $args['city'] = $payloadDetails['city'];
      }
      if (isset($payloadDetails['state'])) {
        $args['state'] = $payloadDetails['state'];
      }
      if (isset($payloadDetails['country'])) {
        $args['country'] = $payloadDetails['country'];
      }
      elseif (isset($payloadDetails['country_code'])) {
        $args['country'] = $payloadDetails['country_code'];
      }
      if (isset($payloadDetails['birthdate_timestamp'])) {
        $args['birthdate'] = date('Y-m-d', $payloadDetails['birthdate_timestamp']);
      }
      if (isset($payloadDetails['birthdate_timestamp'])) {
        $args['BirthYear'] = date('Y', $payloadDetails['birthdate_timestamp']);
      }

      // CGG2014
      if (strtoupper($payloadDetails['application_id']) == 'CGG2014' && isset($payloadDetails['candidate_name'])) {
        $args['CGG2014_1st_vote'] = $payloadDetails['candidate_name'];
      }
      // AGG2015
      if (strtoupper($payloadDetails['application_id']) == 'AGG' && isset($payloadDetails['candidate_name'])) {
        $args['AGG2015_1st_vote'] = $payloadDetails['candidate_name'];
        $args['AGG2015_1st_vote_id'] = $payloadDetails['candidate_id'];
        $args['AGG2015_1st_vote_gender'] = $payloadDetails['candidate_gender'];
      }

      // Set by origin of where user data was collected - typically Message
      // Broker user import but could also be external producers
      if (isset($payloadDetails['source'])) {
        $args['source'] = $payloadDetails['source'];
      }

      echo 'Data sent to Mobile Commons: ' . print_r($args, TRUE), PHP_EOL;

      try {
        $config = array(
          'username' => getenv("MOBILE_COMMONS_USER"),
          'password' => getenv("MOBILE_COMMONS_PASSWORD"),
        );
        $MobileCommons = new MobileCommons($config);
        $status = $MobileCommons->profiles_update($args);

        if (isset($status->error)) {
          echo 'Error - ' . print_r($status->error, TRUE), "\n";
          echo 'Submitted: ' . print_r($args, TRUE), "\n\n";
        }

        // @todo: Watch opted_out_source in response from Mobile Commons to log
        // possible reason for profile addition/update failing.

        echo '-> MBC_RegistrationMobile->profiles_update mobile: ' . $payloadDetails['mobile'] . ' -------', PHP_EOL;

        $this->messageBroker->sendAck($payload);
        $this->statHat->addStatName('profiles_update success');
      }
      catch (Exception $e) {
        trigger_error('mbc-registration-mobile ERROR - Failed to submit "profiles_update" to Mobile Commons API.', E_USER_WARNING);
        echo 'Excecption:' . print_r($e, TRUE), PHP_EOL;
        $this->statHat->addStatName('profiles_update error');
        $this->messageBroker->sendAck($payload);
      }

    }
    elseif (isset($payloadDetails['mobile'])) {
      $this->messageBroker->sendAck($payload);
      $this->statHat->addStatName('non supported activity with provided mobile');
    }
    // No moble number found, remove entry from queue and move on
    else {
      $this->messageBroker->sendAck($payload);
      $this->statHat->addStatName('non mobile');
    }
    $this->statHat->reportCount(1);

    echo '------- MBC_RegistrationMobile END #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', PHP_EOL;
  }

}
