<?php
/**
 * MessagingGroupsBackfillProducer
 */

namespace DoSomething\MessagingGroupsConsumer;

// Native.
use \Exception;

// Contributed.
use League\Csv\Reader;

// DoSomething.
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseProducer;

class MessagingGroupsBackfillProducer extends MB_Toolbox_BaseProducer
{

  public function processSignupCSV() {
    echo '** mbp-user-import->processSignupCSV() '
        . ' START: ' . date('j D M Y G:i:s T') . ' -------' . PHP_EOL;

    // $sql = "
    //   SELECT mob.field_mobile_value AS mobile, c.entity_id AS campaign_id, dd.run_nid AS run_nid, dd.sid
    //   FROM `dosomething_signup` AS `dd`
    //   LEFT JOIN `field_data_field_mobile` AS mob ON mob.entity_id = dd.uid
    //   LEFT JOIN `field_data_field_current_run` as c ON c.field_current_run_target_id = dd.run_nid
    //   WHERE `run_nid` IN (7298, 7280, 7279, 7319, 7324) AND mob.field_mobile_value IS NOT NULL
    // "
    $csv = Reader::createFromPath('./data/ds-signups.csv');

    // Skip first row.
    $data = $csv
      ->setOffset(1)
      ->fetchAssoc(["mobile","campaign_id","run_nid","sid"]);

    foreach ($data as $row) {
      $data = [
        'mobile' => $row['mobile'],
        'application_id' => 'US',
        'activity' => 'campaign_signup',
        'event_id' => $row['campaign_id'],
      ];
      echo '*** Sending ' . json_encode($row) . PHP_EOL;
      $this->produceMessage(
        $this->generatePayload($data),
        'messaging_groups_direct'
      );
    }
  }

  public function processReportbackCSV() {
    echo '** mbp-user-import->processReportbackCSV() '
        . ' START: ' . date('j D M Y G:i:s T') . ' -------' . PHP_EOL;

    // $sql = "
    //   SELECT mob.field_mobile_value AS mobile, dd.nid AS campaign_id, dd.run_nid AS run_nid, dd.rbid
    //   FROM `dosomething_reportback` AS `dd`
    //   LEFT JOIN `field_data_field_mobile` AS mob ON mob.entity_id = dd.uid
    //   WHERE `run_nid` IN (7298, 7280, 7279, 7319, 7324) AND mob.field_mobile_value IS NOT NULL
    // "
    $csv = Reader::createFromPath('./data/ds-reportbacks.csv');

    // Skip first row.
    $data = $csv
      ->setOffset(1)
      ->fetchAssoc(["mobile","campaign_id","run_nid","sid"]);

    foreach ($data as $row) {
      $data = [
        'mobile' => $row['mobile'],
        'application_id' => 'US',
        'activity' => 'campaign_reportback',
        'event_id' => $row['campaign_id'],
      ];
      echo '*** Sending ' . json_encode($row) . PHP_EOL;
      $this->produceMessage(
        $this->generatePayload($data),
        'messaging_groups_direct'
      );
    }
  }


}
