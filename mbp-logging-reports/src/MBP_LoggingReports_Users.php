<?php
/**
 * MBP_LoggingReports_Users - class to manage importing user data via CSV files to the
 * Message Broker system.
 */

namespace DoSomething\MBP_LoggingReports;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use \Exception;

/**
 * MBP_LoggingReports_users class - Generate reports of user imports based on log
 * entries in the mb-logging database.
 */
class MBP_LoggingReports_Users
{

  const MB_LOGGING_API = '/api/v1';

  /**
   * Message Broker connection to send messages to send email request for report message.
   *
   * @var object $messageBroker_Subscribes
   */
  protected $messageBroker;

  /**
   * Message Broker Toolbox cURL utilities.
   *
   * @var object $mbToolboxCURL
   */
  private $mbToolboxCURL;

  /**
   * mb-logging-api base URL.
   *
   * @var array $mbLoggingAPIUrl
   */
  private $mbLoggingAPIUrl;

  /**
   * Setting from external services - Mailchimp.
   *
   * @var array
   */
  private $statHat;

  /**
   * List of allowed sources currently supported.
   *
   * @var array
   */
  private $allowedSources;

  /**
   * Constructor for MBC_LoggingReports
   */
  public function __construct() {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBroker = $this->mbConfig->getProperty('messageBroker');
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');
    $this->mbLoggingAPIUrl = $mbLoggingAPIConfig['host'] . ':' . $mbLoggingAPIConfig['port'];
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->allowedSources = unserialize(ALLOWED_SOURCES);
  }

  /**
   * report() - Request a report be sent.
   *
   * @param string $type
   *   The type or collection of types of report to generate
   * @param string $source
   *   The import source: Niche or After School
   * @param array $recipients
   *   List of addresses (email and/or SMS phone numbers)
   */
  public function report($type, $source, $recipients = null) {

    switch($type) {

      case 'runningMonth':

        $reportData['userImportCSV'] = $this->collectData('userImportCSV', $source);
        $reportData['existingUsers'] = $this->collectData('existingUsers', $source);
        $composedReport = $this->composedReportMarkup($reportData);
        break;

      default:

        throw new Exception('Unsupported report type: ' . $type);
        break;
    }

    if (empty($recipients)) {
      $recipients = $this->getRecipients();
    }
    $this->dispatchReport($composedReport, $recipients);
  }

  /**
   * Controller for report data collection.
   *
   * @param string $type
   *   The type of report generate: userImportCSV, existingUsers, (additional format to follow)
   * @param string $source
   *   The name of the user import source: niche, afterschool, all
   * @param string $startDate
   *   The date to start reports from. Defaults to start of month
   * @param string $endDate
   *   The date to end reports from. Defaults to start of today.
   */
  private function collectData($type, $source, $startDate = null, $endDate = null) {

    if (!in_array($source, $this->allowedSources)) {
      throw new Exception('Unsupported source: ' . $source);
    }

    if ($startDate != null) {
      $startDateStamp = date('Y-m', strtotime($targetStartDate)) . '-01';;
    }
    else {
      $startDateStamp = strtotime('first day of this month');
    }
    if ($endDate != null) {
      $endDateStamp = date('Y-m', strtotime($targetStartDate . ' + 1 month')) . '-01';
    }
    else {
      $endDateStamp = strtotime('today midnight');
    }

    // Existing user imports from $source
    if ($type = 'userImportCSV') {
      $reportData['userImportCSV'] = $this->collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp);
    }

    if ($type = 'existingUsers') {
      $reportData['existingUsers'] = $this->collectExistingUserImportLogEntries($source, $startDateStamp, $endDateStamp);
    }

    if (!empty($reportData)) {
      return $reportData;
    }
    else {
      throw new Exception('composedReportData not defined.');
    }
  }

  /**
   * Collect log entries on processed CSV files.
   *
   * @param string $source
   *   The target source - one of niche, afterschool
   * @param integer startDateStamp
   *   The datestamp to start reports from
   * @param integer endDateStamp
   *   The datestamp to end reports from
   *
   * @return array
   *   Collected log entries
   */
  private function collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp) {

    $stats[$source]['filesProcessed'] = 0;
    $stats[$source]['usersProcessed'] = 0;

    $targetStartDate = date('Y-m-d', $startDateStamp);
    $targetEndDate = date('Y-m-d', $endDateStamp);
    $curlUrl = $this->mbLoggingAPIUrl . '/api/v1/imports/summaries?type=user_import&source=' . strtolower($source) . '&origin_start=' . $targetStartDate . '&origin_end=' . $targetEndDate;

    $results = $this->mbToolboxCURL->curlGET($curlUrl);
    if ($results[1] != 200) {
      throw new Exception('Call to ' . $curlUrl . ' returned: ' . $results[1]);
    }
    $numberOfFiles = count($results[0]) - 1;

    $stats[$source]['startDate'] = $targetStartDate;
    $stats[$source]['endDate'] = $targetEndDate;
    $stats[$source]['numberOfFiles'] = $numberOfFiles;
    $stats[$source]['firstFile'] = $results[0][0]->target_CSV_file;
    $stats[$source]['lastFile'] = $results[0][$numberOfFiles]->target_CSV_file;

    foreach ($results[0] as $resultCount => $result) {
      $stats[$source]['filesProcessed']++;
      $stats[$source]['usersProcessed'] += $result->signup_count;
    }

    return $stats;
  }

  /**
   * Collect duplicate user import log entries.
   *
   * @param string $source
   *   The target source - one of niche, afterschool
   * @param integer startDateStamp
   *   The datestamp to start reports from
   * @param integer endDateStamp
   *   The datestamp to end reports from
   *
   * @return array
   *   Collected log entries
   */
  private function collectExistingUserImportLogEntries($source, $startDateStamp, $endDateStamp) {

    $targetStartDate = date('Y-m-d', $startDateStamp);
    $targetEndDate = date('Y-m-d', $endDateStamp);
    $curlUrl = $this->mbLoggingAPIUrl . '/api/v1/imports?type=user_import&source=' . strtolower($source) . '&origin_start=' . $targetStartDate . '&origin_end=' . $targetEndDate;

    $results = $this->mbToolboxCURL->curlGET($curlUrl);
    if ($results[1] != 200) {
      throw new Exception('Call to ' . $curlUrl . ' returned: ' . $results[1]);
    }

    $stats[$source]['startDate'] = $targetStartDate;
    $stats[$source]['endDate'] = $targetEndDate;
    $stats[$source]['total'] = count($results);

    foreach ($results[0] as $resultCount => $result) {

      if (isset($result->email)) {
        $stats[$source]['existingMailchimpUser']++;
      }
      if (isset($result->phone)) {
        if ($result->phone->status == 'Active Subscriber') {
          $stats[$source]['mobileCommonsUserError_existing']++;
        }
        elseif ($result->phone->status == 'Undeliverable') {
          $stats[$source]['mobileCommonsUserError_undeliverable']++;
        }
        elseif ($result->phone->status == 'No Subscriptions') {
          $stats[$source]['mobileCommonsUserError_noSubscriptions']++;
        }
        else {
          $stats[$source]['mobileCommonsUserError_other']++;
        }
      }
      if (isset($result->drupal)) {
        $stats[$source]['existingDrupalUser']++;
      }

    }

    return $stats;
  }

  /**
   * Compose the contents of the existing users import report content.
   *
   * @param stats array
   *   Details of the user accounts that existed in Mailchimp, Mobile Common
   *   and/or Drupal at the time of import.
   *
   * @return string
   *   The text to be displayed in the report.
   */
  private function composedReportMarkup($reportData) {

    $reportContents = '';

    foreach ($reportData['source'] as $source => $data) {
      $reportContents = 'Report contents: '. $source;
    }

    return $reportContents;
  }

  /**
   * Compose the contents of the existing users import report content.
   *
   * @param stats array
   *   Details of the user accounts that existed in Mailchimp, Mobile Common
   *   and/or Drupal at the time of import.
   *
   * @return string
   *   The text to be displayed in the report.
   */
  private function getRecipients() {

    $to = [
      [
        'email' => 'dlee@dosomething.org',
        'name' => 'Dee'
      ],
    ];

    return $to;
  }

  /**
   * Send report to appropriate managers.
   *
   * @param string $existingUsersReport string
   *   Details of the summary log entries for each import batch.
   * @param array $recipients
   *   A list of users to send the report to.
   */
  private function dispatchReport($composedReport, $recipients) {
    
    $memberCount = $this->toolbox->getDSMemberCount();

    foreach ($tos as $to) {
      $message = array(
        'from_email' => 'machines@dosomething.org',
        'email' => $to['email'],
        'activity' => 'mb-reports',
        'email_template' => 'mb-user-import-report',
        'merge_vars' => array(
          'FNAME' => $to['name'],
          'SUBJECT' => 'Daily User Import Report - ' . date('Y-m-d'),
          'TITLE' => date('Y-m-d') . ' - Daily User Imports',
          'BODY' => $existingUsersReport,
          'MEMBER_COUNT' => $memberCount,
        ),
        'email_tags' => array(
          0 => 'mb-user-import-report',
        ),
      );
      $payload = serialize($message);
      $this->messageBroker->publishMessage($payload);
    }

  }

}
