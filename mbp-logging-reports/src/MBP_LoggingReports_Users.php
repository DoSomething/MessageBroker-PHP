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
   * mb-logging-api configuration settings.
   *
   * @var array $mbLoggingAPIConfig
   */
  private $mbLoggingAPIConfig;

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
    $this->mbLoggingAPIConfig = $this->mbConfig->getProperty('mb_logging_api_config');
    $this->statHat = $this->mbConfig->getProperty('statHat');
  }

  /**
   * Controller for report generation.
   *
   * @param string $type
   *   The type of report generate: userImportCSV, existingUsers, (additional format to follow)
   * @param string $source
   *   The name of the user import source: Niche, AfterSchool, All
   * @param string $startDate
   *   The date to start reports from. Defaults to start of month
   * @param string $endDate
   *   The date to end reports from. Defaults to start of today.
   */
  public function generateReport($type, $source = 'all', $startDate = null, $endDate = null) {

    if ($type = 'user' && !in_array($source, $this->allowedSources)) {
      throw new Exception('Unsupported source: ' . $source);
    }

    if ($startDate == null) {
      $startDateStamp = date('Y-m', strtotime($targetStartDate)) . '-01';;
    }
    else {
      $startDateStamp = '';
    }
    if ($endDate == null) {
      $endDateStamp = date('Y-m', strtotime($targetStartDate . ' + 1 month')) . '-01';
    }
    else {
      $endDateStamp = '';
    }

    // Existing user imports from $source
    if ($type = 'userImportCSV') {
      $userImportCSVEntries = $this->collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp);
      $composedReportData = $this->composeReport($userImportCSVEntries);
    }

    if ($type = 'existingUsers') {
      $existingUserImportLogEntries = $this->collectExistingUserImportLogEntries($source, $startDateStamp, $endDateStamp);
      $composedReportData = $this->composeExistingUserImportLogEntriesReport($existingUserImportLogEntries);
    }

    if (!empty($composedReportData)) {
      $this->dispatchReport($composedReportData);
    }
    else {
      throw new Exception('composedReportData not defined.');
    }
  }

  
  
  
  /**
   * Collect duplicate user import log entries.
   *
   * @param startDate string
   *   The date to start reports from
   * @param endDate string
   *   The date to end reports from
   * @return array
   *   Collected log entries
   */
  private function collectImportLogEntries($startDate = null, $endDate = null, $source = 'all') {

    // Define source list
    if ($source == 'all') {
      $sources = $this->gatherSources($duration, $targetStartDate);
    }
    else {
      $sources[0] = $source;
    }

    $stats = array();
    $types = array('user_import', 'summary');
    if ($targetStartDate == 0) {
      $targetStartDate = date('Y-m-d');
    }

    if ($duration == 'day') {
      $targetEndDate = date('Y-m-d', strtotime($targetStartDate . ' + 2 day'));
    }
    elseif ($duration == 'week') {
      $targetDate = $targetStartDate;
      $targetStartDate = date('Y-m-d', strtotime($targetDate . ' - 6 day'));
      $targetEndDate = date('Y-m-d', strtotime($targetDate . ' + 2 day'));
    }
    elseif ($duration == 'month') {
      $targetStartDate = date('Y-m', strtotime($targetStartDate)) . '-01';
      $targetStartDate = date('Y-m-d', strtotime($targetStartDate . ' + 1 day'));
      $targetEndDate = date('Y-m', strtotime($targetStartDate . ' + 1 month')) . '-01';
      $targetEndDate = date('Y-m-d', strtotime($targetEndDate . ' + 2 day'));
    }
    else {
      $targetEndDate = 0;
    }
    $stats['targetStartDate'] = $targetStartDate;

    $baseUrl = getenv('DS_LOGGING_API_HOST') . ':' . getenv('DS_LOGGING_API_PORT');

    foreach ($sources as $source) {
      foreach ($types as $type) {

        if ($type == 'user_import') {
          $loggingApiUrl = $baseUrl . '/api/v1/imports/' . $targetStartDate . '/' . $targetEndDate . '?type=user_import&exists=1&source=' . $source;
        }
        else {
          $loggingApiUrl = $baseUrl . '/api/v1/imports/summaries/' . $targetStartDate . '/' . $targetEndDate . '?type=user_import&exists=1&source=' . $source;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $loggingApiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $jsonResult = curl_exec($ch);
        curl_close($ch);
        $results = json_decode($jsonResult);

        if ($type == 'user_import') {

          $stats[$source]['existingMailchimpUser'] = 0;
          $stats[$source]['mobileCommonsUserError_existing'] = 0;
          $stats[$source]['mobileCommonsUserError_undeliverable'] = 0;
          $stats[$source]['mobileCommonsUserError_noSubscriptions'] = 0;
          $stats[$source]['mobileCommonsUserError_other'] = 0;
          $stats[$source]['existingDrupalUser'] = 0;
          $stats[$source]['totalExisting'] = 0;
          $resultCount = 0;

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
          $stats[$source]['totalExisting'] = $resultCount - $stats[$source]['mobileCommonsUserError_undeliverable'];

        }
        else {

          foreach ($results as $result) {
            $stats[$source]['summaries'][] = array(
              'target_CSV_file' => $result->target_CSV_file,
              'logged_date' => $result->logged_date,
              'signup_count' => $result->signup_count,
              'skipped' => $result->skipped
            );
          }

        }

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
