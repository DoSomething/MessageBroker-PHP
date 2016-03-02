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
   * Constructor for MBC_LoggingReports
   */
  public function __construct() {
    
    parent:: __construct();
    $this->mbConfig = MB_Configuration::getInstance();
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');
    $this->mbLoggingAPIUrl = $mbLoggingAPIConfig['mb_logging_api_host'] . ':' . $mbLoggingAPIConfig['mb_logging_api_port'];
    $this->statHat = $this->mbConfig->getProperty('statHat');
  }

  /**
   * report() - Request a report be sent.
   *
   * @param string $type
   *  The type or collection of types of report to generate
   * @param array $recipients
   *   List of addresses (email and/or SMS phone numbers)
   */
  public function report($type, $recipients = []) {

    switch($type) {

      case 'nicheRunningMonth':

        $reportData['userImportCSV'] = $this->collectData('userImportCSV', 'niche');
        $reportData['existingUsers'] = $this->collectData('existingUsers', 'niche');
        $composedReportMarkup = $this->composedReportMarkup($reportData);
        break;

      default:

        throw new Exception('Unsupported report type: ' . $type);
        break;
    }

    $this->dispatchReport($composedReportMarkup, $recipients);
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
  private function collectData($type, $source = 'all', $startDate = null, $endDate = null) {

    if ($type = 'user' && !in_array($source, $this->allowedSources)) {
      throw new Exception('Unsupported source: ' . $source);
    }

    if ($startDate == null) {
      $startDateStamp = date('Y-m', strtotime($targetStartDate)) . '-01';;
    }
    else {
      $startDateStamp = strtotime('first day of this month');
    }
    if ($endDate == null) {
      $endDateStamp = date('Y-m', strtotime($targetStartDate . ' + 1 month')) . '-01';
    }
    else {
      $endDateStamp = strtotime('today midnight');
    }

    // Existing user imports from $source
    if ($type = 'userImportCSV') {
      $reportData = $this->collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp);
    }

    if ($type = 'existingUsers') {
      $reportData = $this->collectExistingUserImportLogEntries($source, $startDateStamp, $endDateStamp);
    }

    if (!empty($reportData)) {
      return $reportData;
    }
    else {
      throw new Exception('composedReportData not defined.');
    }
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
  private function collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp) {

    $targetStartDate = date('Y-m-d', $startDateStamp);
    $targetEndDate = date('Y-m-d', $endDateStamp);
    $curlUrl = $this->mbLoggingAPIUrl . '/api/v1/imports?type=user_import&source=' . strtolower($source) . '&origin_start=' . $targetStartDate . '&origin_end=' . $targetEndDate;

    $results = $this->mbToolboxCURL->curlGET($curlUrl);

    $stats[$source]['startDate'] = $targetStartDate;
    $stats[$source]['endDate'] = $targetEndDate;
    $stats[$source]['total'] = count($results);

    foreach ($results as $resultCount => $result) {

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
  private function composeReport($stats) {

    $reportContents = '';

    foreach ($stats as $source => $statDetails) {
      if (isset($statDetails[$source]['summaries'])) {
        $summaryContents  = '<h3>Import Summary</h1>' . "\n";
        $summaryContents .= '<table padding="1" style="width:100%; border: 1px solid lightgrey; margin-bottom: 1em;">' . "\n";
        $summaryContents .= '  <tr style="background-color: black; color: white;">' . "\n";
        $summaryContents .= '    <td>Import File</td>' . "\n";
        $summaryContents .= '    <td>Import Submissions</td>' . "\n";
        $summaryContents .= '    <td>Skipped</td>' . "\n";
        $summaryContents .= '    <td>Existing DS Users</td>' . "\n";
        $summaryContents .= '    <td>New Users</td>' . "\n";
        $summaryContents .= '  </tr>' . "\n";
        $signup_total = 0;
        foreach($statDetails[$source]['summaries'] as $summaryCount => $summary) {
          $signup_total += $summary['signup_count'];
          $newUsersImported = $signup_total - $summary['skipped'] - $statDetails[$source]['totalExisting'];
          $summaryContents .= '  <tr>' . "\n";
          $summaryContents .= '    <td>' . $summary['target_CSV_file'] . '</td>' . "\n";
          $summaryContents .= '    <td>' . $summary['signup_count'] . '</td>' . "\n";
          $summaryContents .= '    <td>' . $summary['skipped'] . '</td>' . "\n";

          // Only display totals on last row
          if ($summaryCount == count($statDetails[$source]['summaries']) - 1) {
            $summaryContents .= '    <td>' . $statDetails[$source]['totalExisting'] . '</td>' . "\n";
            $summaryContents .= '    <td>' . $newUsersImported . '</td>' . "\n";
          }
          else {
            $summaryContents .= '    <td>&nbsp;</td>' . "\n";
            $summaryContents .= '    <td>&nbsp;</td>' . "\n";
          }

          $summaryContents .= '  </tr>' . "\n";
        }
        $summaryContents .= '</table>' . "\n";
        $reportContents .= $summaryContents;
      }

      $existingMobileCommonsTotal = 0;
      $existingMobileCommons = '';

      if (isset($statDetails[$source]['mobileCommonsUserError_undeliverable'])) {
        $existingMobileCommons .= 'Undeliverable: ' . $statDetails[$source]['mobileCommonsUserError_undeliverable'] . '<br /><br />';
      }
      if (isset($statDetails[$source]['mobileCommonsUserError_existing'])) {
        $existingMobileCommonsTotal += $statDetails[$source]['mobileCommonsUserError_existing'];
        $existingMobileCommons .= 'Existing: ' . $statDetails[$source]['mobileCommonsUserError_existing'] . '<br />';
      }
      if (isset($statDetails[$source]['mobileCommonsUserError_noSubscriptions'])) {
        $existingMobileCommonsTotal += $statDetails[$source]['mobileCommonsUserError_noSubscriptions'];
        $existingMobileCommons .= 'No Subscription: ' . $statDetails[$source]['mobileCommonsUserError_noSubscriptions'] . '<br />';
      }
      if (isset($statDetails[$source]['mobileCommonsUserError_other']) && $statDetails[$source]['mobileCommonsUserError_other'] > 0) {
        $existingMobileCommonsTotal += $statDetails[$source]['mobileCommonsUserError_other'];
        $existingMobileCommons .= 'Other: ' . $statDetails[$source]['mobileCommonsUserError_other'] . '<br />';
      }

      if ($existingMobileCommonsTotal > 0) {
        $existingMobileCommons .= '=============== <br />';
        $existingMobileCommons .= 'Total: ' . $existingMobileCommonsTotal;
      }

      if (isset($statDetails[$source]['totalExisting']) && $statDetails[$source]['totalExisting'] != 0) {

        $existingContent  = '<h3>Existing User Details</h1>' . "\n";
        $existingContent .= '<table padding="1" style="width:100%; border: 1px solid lightgrey; margin-bottom: 1em;">' . "\n";
        $existingContent .= '  <tr style="background-color: black; color: white;">' . "\n";
        $existingContent .= '    <td>Mailchimp</td>' . "\n";
        $existingContent .= '    <td>Drupal</td>' . "\n";
        $existingContent .= '    <td>Mobile Commons</td>' . "\n";
        $existingContent .= '    <td>Total Existing</td>' . "\n";
        $existingContent .= '  </tr>' . "\n";

        $existingContent .= '  <tr>' . "\n";
        $existingContent .= '    <td>' . $statDetails[$source]['existingMailchimpUser'] . '</td>' . "\n";
        $existingContent .= '    <td>' . $statDetails[$source]['existingDrupalUser'] . '</td>' . "\n";
        $existingContent .= '    <td>' . $existingMobileCommons . '</td>' . "\n";
        $existingContent .= '    <td>' . $statDetails[$source]['totalExisting']  . '</td>' . "\n";
        $existingContent .= '  </tr>' . "\n";

        $existingContent .= '</table>' . "\n";
        $reportContents .= $existingContent;

      }
      else {
        $reportContents = 'Oppps, nothing was logged since ' . $statDetails[$source]['targetStartDate'] . '.';
      }
    }

    return $reportContents;
  }

  /**
   * Send report to appropriate managers.
   *
   * @param existingUsersReport string
   *   Details of the summary log entries for each import batch.
   */
  private function dispatchReport($existingUsersReport) {
    
    $memberCount = $this->toolbox->getDSMemberCount();

    $tos = array(
      0 => array(
        'email' => 'dlee@dosomething.org',
        'name' => 'Dee'
      ),
      1 => array(
        'email' => 'mlidey@dosomething.org',
        'name' => 'Marah'
      ),
      2 => array(
        'email' => 'mholford@dosomething.org',
        'name' => 'Matt'
      ),
      3 => array(
        'email' => 'jbladt@dosomething.org',
        'name' => 'Jeff'
      ),
      4 => array(
        'email' => 'juy@dosomething.org',
        'name' => 'Jonathan'
      ),
    );

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
