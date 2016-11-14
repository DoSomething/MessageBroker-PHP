<?php
/**
 * MessagingGroupsBackfillProducer
 */

namespace DoSomething\MessagingGroupsConsumer;

use \Exception;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseProducer;


class MessagingGroupsBackfillProducer extends MB_Toolbox_BaseProducer
{

  public function processSignupCSV() {
    echo '** mbp-user-import->processSignupCSV() '
        . ' START: ' . date('j D M Y G:i:s T') . ' -------' . PHP_EOL;

    $data = [
      'mobile' => '',
      'application_id' => 'US',
      'activity' => 'signup',
      'event_id' => '',
    ];

    $payload = $this->generatePayload($data);
    parent->produceMessage($payload, 'messaging_groups_direct');

  }

  public function processReportbackCSV() {
    echo '** mbp-user-import->processReportbackCSV() '
        . ' START: ' . date('j D M Y G:i:s T') . ' -------' . PHP_EOL;
  }

}
