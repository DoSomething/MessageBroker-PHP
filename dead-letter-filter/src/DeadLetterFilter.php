<?php
/**
 * DeadLetterFilter
 */

namespace DoSomething\DeadLetter;

use \Exception;

use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;

class DeadLetterFilter extends MB_Toolbox_BaseConsumer
{

  const TEXT_QUEUE_NAME = 'deadLetterQueue';

  /**
   * Constructor compatible with MBC_BaseConsumer.
   */
  public function __construct($targetMBconfig = 'messageBroker', $args) {
    parent::__construct($targetMBconfig);

    // Northstar.
    $this->northstar = $this->mbConfig->getProperty('northstar');
  }

  /**
   * Initial method triggered by blocked call in dead-letter-filter.
   *
   * @param array $messages
   *   The contents of the queue entry message being processed.
   */
  public function filterDeadLetterQueue($letters) {
    echo '------ dead-letter-filter - DeadLetterFilter->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    $this->letters = $letters;

    foreach ($this->letters as $key => $letter) {
      $body = $letter->getBody();
      if ($this->isSerialized($body)) {
        $payload = unserialize($body);
      } else {
        $payload = json_decode($body, true);
      }

      $original = &$payload['message'];

      // Check that message is decoded correctly.
      if (!$payload) {
        $this->log('Corrupted message: %s', $body);
        $this->reject($key);
        continue;
      }

      // Check that message is qualified for this consumer.
      if (!$this->canProcess($payload)) {
        $this->log('Rejected: %s', json_encode($original));
        $this->reject($key);
        continue;
      }

      // Process
      $this->handleError($payload, $key);
    }

    echo  PHP_EOL . '------ dead-letter-filter - DeadLetterFilter->filterDeadLetterQueue() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
  }

  protected function canProcess($payload) {
    // deadLetter v2:
    if (!empty($payload['message']) && !empty($payload['metadata'])) {
      $original = &$payload['message'];

      // Skip countries.
      $disabledAppIds = ['CA'];
      if (!empty($original['application_id'])
          && in_array($original['application_id'], $disabledAppIds)) {
        $this->log(
          'Skipping disabled application id %s',
          $original['application_id']
        );
        return false;
      }

    }

    return true;
  }

  private function handleError($payload, $key) {
    // Resolve kind of the issue.
    // 1. Handle Niche alleged duplicates
    $isNicheDuplicatesError = !empty($payload['message']['tags'])
      && in_array('current-user-welcome-niche', $payload['message']['tags'])
      && !empty($payload['metadata'])
      && !empty($payload['metadata']['error'])
      && !empty($payload['metadata']['error']['locationText'])
      && $payload['metadata']['error']['locationText'] === 'processOnGambit';


    if ($isNicheDuplicatesError) {
      try {
        $this->handleNicheAlledgedDuplicates($payload['message'], $key);
      } catch (Exception $e) {
        self::log('Unexpected error: %s', $e->getMessage());
      }
      return;
    }
  }

  private function handleNicheAlledgedDuplicates($original, $key) {
    if (empty($original['email']) && empty($original['mobile'])) {
      $this->log('NICHE: No email and mobile, skipping: %s', json_encode($original));
      $this->resolve($key);
      return;
    }
    echo '-- Fixing Northstar info for ' . json_encode($original) . PHP_EOL;

    // Lookup on Northstar by email.
    $identityByEmail = $this->northstar->getUser('email', $original['email']);
    if (!empty($original['mobile'])) {
      $identityByMobile = $this->northstar->getUser('mobile', $original['mobile']);
    } else {
      $identityByMobile = false;
    }

    // Process all possible cases and merge data based on identity load results:
    if (empty($identityByEmail) && empty($identityByMobile)) {
      // ****** New user ******
      self::log('User not found, skipping: %s', json_encode($original));
      $this->resolve($key);
      return;
    }

    if (!empty($identityByEmail) && empty($identityByMobile)) {
      // ****** Existing user: only email record exists ******
      $identity = &$identityByEmail;
      self::log(
        'User identified by email %s as %s',
        $original['email'],
        $identity->id
      );

      // Save mobile number to record loaded by email.
      if (!empty($original['mobile'])) {
        self::log(
          'Updating user %s mobile phone from "%s" to "%s"',
          $identity->id,
          ($identity->mobile ?: "NULL"),
          $original['mobile']
        );

        $params = ['mobile' => $original['mobile']];
        if (!DRY_RUN) {
          $identity = $this->northstar->updateUser($identity->id, $params);
        }
      }

      $this->resolve($key);
      return;
    }

    if (!empty($identityByMobile) && empty($identityByEmail)) {
      // ****** Existing user: only mobile record exists ******
      $identity = &$identityByMobile;
      self::log(
        'User identified by mobile %s as %s',
        $original['mobile'],
        $identity->id
      );

      // Save email to record loaded by mobile.
      self::log(
        'Updating user %s email from "%s" to "%s"',
        $identity->id,
        ($identity->email ?: "NULL"),
        $original['email']
      );
      $params = ['email' => $original['email']];
      if (!DRY_RUN) {
        $identity = $this->northstar->updateUser($identity->id, $params);
      }
      $this->resolve($key);
      return;
    }

    if ($identityByEmail->id !== $identityByMobile->id) {
      // ****** Existing users: loaded both by mobile and phone ******
      // We presume that user account with mobile number generally have
      // email address as well. For this reason we decided to use
      // identity loaded by mobile rather than by email.
      $identity = &$identityByMobile;

      self::log(
        'User identified by email %s as %s and by mobile %s as %s',
        $original['mobile'],
        $identityByMobile->id,
        $original['email'],
        $identityByEmail->id
      );

      $this->resolve($key);
      return;
    }

    if ($identityByEmail->id === $identityByMobile->id) {
      // ****** Existing user: same identity loaded both by mobile and phone ******
      $identity = &$identityByEmail;

      self::log(
        'User identified by mobile %s and email %s: %s',
        $original['mobile'],
        $original['email'],
        $identity->id
      );

      $this->resolve($key);
      return;
    }

    self::log(
      'This will only execute when user identity logic is broken, payload: %s',
      json_encode($original)
    );
    return;
  }

  private function reject($key) {
    if (!DRY_RUN) {
      $this->messageBroker->sendNack($this->letters[$key], false, false);
    }
    unset($this->letters[$key]);
  }

  private function resolve($key) {
    if (!DRY_RUN) {
      $this->messageBroker->sendAck($this->letters[$key]);
    }
    unset($this->letters[$key]);
  }

  /**
   * Log
   */
  static function log()
  {
    $args = func_get_args();
    $message = array_shift($args);
    echo '** ';
    echo vsprintf($message, $args);
    echo PHP_EOL;
  }

  /**
   * Bad OOP IS BAD.
   */
  protected function setter($arguments) {}
  protected function process($payload) {}

}
