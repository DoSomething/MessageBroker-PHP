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
  const AFTERSCHOOL_USER_BUDGET = 'Unlimited';

  // Monthly user budget
  private static $NICHE_USER_BUDGET = [
    1 => 33333,
    2 => 33333,
    3 => 33333,
    4 => 33333,
    5 => 33333,
    6 => 48435,
    7 => 33333,
    8 => 33333,
    9 => 33333,
    10 => 33333,
    11 => 33333,
    12 => 33333,
  ];


  /**
   * Message Broker connection to send messages to send email request for report message.
   *
   * @var object $messageBroker_Subscribes
   */
  protected $messageBroker;

  /**
   * Message Broker Toolbox utilities.
   *
   * @var object $mbToolbox
   */
  private $mbToolbox;

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
    $this->mbToolbox = $this->mbConfig->getProperty('mbToolbox');
    $this->mbToolboxCURL = $this->mbConfig->getProperty('mbToolboxcURL');
    $this->slack = $this->mbConfig->getProperty('mbSlack');
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
   * @param string $sources
   *   The import source: Niche or After School
   * @param string $startDate
   *   The start date for the report
   * @param array $recipients
   *   List of addresses (email and/or SMS phone numbers)
   */
  public function report($type, $sources, $startDate = null, $recipients = null) {

    // @todo: Coordinate sending reports. Includes to who based on $budgetStatus
    // $budgetStatus = $this->budgetStatus('niche');
    // $this->dispatchReport($type, $budgetStatus);
    if (empty($recipients)) {
      $recipients = $this->getRecipients($type, $sources);
    }

    switch($type) {

      case 'runningMonth':

        foreach ($sources as $source) {

          $reportData[$source]['userImportCSV'] = $this->collectData('userImportCSV', $source, $startDate);
          $reportData[$source]['existingUsers'] = $this->collectData('existingUsers', $source, $startDate);
          $reportData[$source]['newUsers'] = $reportData[$source]['userImportCSV']['usersProcessed'] - $reportData[$source]['existingUsers']['total'];
          $percentNewUsers = ($reportData[$source]['userImportCSV']['usersProcessed'] - $reportData[$source]['existingUsers']['total']) / $reportData[$source]['userImportCSV']['usersProcessed'] * 100;
          $reportData[$source]['percentNewUsers'] = round($percentNewUsers, 1);
          $status = $this->budgetStatus($source, $type, $reportData[$source]['newUsers']);

          $reportData[$source]['budgetPercentage'] = $status['budgetPercentage'];
          $reportData[$source]['budgetBackgroundColor'] = $status['budgetBackgroundColor'];
          $reportData[$source]['budgetProjectedCompletion'] = $status['budgetProjectedCompletion'];

          $composedReport = $this->composedReportMarkupEmail($reportData);
          $this->dispatchEmailReport($source, $composedReport, $recipients, $status['budgetState']);

          $composedReport = $this->composedReportMarkupSlack($reportData);
          $this->dispatchSlackAlert($source, $composedReport, $recipients, $status['budgetState']);
        }

        break;

      default:

        throw new Exception('Unsupported report type: ' . $type);
        break;
    }

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
      $startDateStamp = strtotime($startDate);
    }
    else {
      $startDateStamp = mktime(0, 0, 0, date('n'), 1, date('Y'));
    }
    if ($endDate != null) {
      $endDateStamp = date('Y-m', strtotime($targetStartDate . ' + 1 month')) . '-01';
    }
    else {
      $endDateStamp = mktime(0, 0, 0, date('n'), date('j') + 1, date('Y'));
    }

    // Existing user imports from $source
    if ($type == 'userImportCSV') {
      $reportData = $this->collectUserImportCSVEntries($source, $startDateStamp, $endDateStamp);
    }
    if ($type == 'existingUsers') {
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

    $stats['filesProcessed'] = 0;
    $stats['usersProcessed'] = 0;

    $targetStartDate = date('Y-m-d', $startDateStamp);
    $targetEndDate = date('Y-m-d', $endDateStamp);
    $curlUrl = $this->mbLoggingAPIUrl . '/api/v1/imports/summaries?type=user_import&source=' . strtolower($source) . '&origin_start=' . $targetStartDate . '&origin_end=' . $targetEndDate;

    $results = $this->mbToolboxCURL->curlGET($curlUrl);
    if ($results[1] != 200) {
      throw new Exception('Call to ' . $curlUrl . ' returned: ' . $results[1]);
    }
    $numberOfFiles = count($results[0]) - 1;

    $stats['startDate'] = $targetStartDate;
    $stats['endDate'] = $targetEndDate;
    $stats['numberOfFiles'] = $numberOfFiles;
    $stats['firstFile'] = $results[0][0]->target_CSV_file;
    $stats['lastFile'] = $results[0][$numberOfFiles]->target_CSV_file;

    foreach ($results[0] as $resultCount => $result) {
      $stats['filesProcessed']++;
      $stats['usersProcessed'] += $result->signup_count;
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

    $stats['existingMailchimpUser'] = 0;
    $stats['mobileCommonsUserError_existing'] = 0;
    $stats['mobileCommonsUserError_undeliverable'] = 0;
    $stats['mobileCommonsUserError_noSubscriptions'] = 0;
    $stats['mobileCommonsUserError_other'] = 0;
    $stats['existingDrupalUser'] = 0;

    $stats['startDate'] = $targetStartDate;
    $stats['endDate'] = $targetEndDate;
    $stats['total'] = count($results[0]);

    foreach ($results[0] as $resultCount => $result) {

      if (isset($result->email)) {
        $stats['existingMailchimpUser']++;
      }
      if (isset($result->phone)) {
        if ($result->phone->status == 'Active Subscriber') {
          $stats['mobileCommonsUserError_existing']++;
        }
        elseif ($result->phone->status == 'Undeliverable') {
          $stats['mobileCommonsUserError_undeliverable']++;
        }
        elseif ($result->phone->status == 'No Subscriptions') {
          $stats['mobileCommonsUserError_noSubscriptions']++;
        }
        else {
          $stats['mobileCommonsUserError_other']++;
        }
      }
      if (isset($result->drupal)) {
        $stats['existingDrupalUser']++;
      }

    }

    return $stats;
  }

  /**
   * Compose the contents of the existing users import report content for email.
   *
   * @param stats array
   *   Details of the user accounts that existed in Mailchimp, Mobile Common
   *   and/or Drupal at the time of import.
   *
   * @return string
   *   The text to be displayed in the report.
   */
  private function composedReportMarkupEmail($reportData) {

    $reportContents  = '<table style ="border-collapse:collapse; width:100%; white-space:nowrap; border:1px solid black; padding:8px; text-align: center;">' . PHP_EOL;
    $reportContents .= '  <tr style ="border:1px solid white; padding:3px; background-color: black; color: white; font-weight: heavy;"><td></td><td>Users Processed</td><td>Existing Users</td><td>New Users</td><td>Budget</td></tr>' . PHP_EOL;

    foreach ($reportData as $source => $data) {

      $reportTitle = '<strong>' . $data['userImportCSV']['startDate'] . ' - ' . $data['userImportCSV']['endDate'] . '</strong>' . PHP_EOL;
      $reportContents .= '
        <tr style ="border:1px solid black; padding:5px; background-color: grey; color: black;">
          <td style="text-align: right; font-size: 1.3em; font-weight: heavy; background-color: black; color: white;">' . $source . ':&nbsp;</td>
          <td style="background-color: white;">' . $data['userImportCSV']['usersProcessed'] . '</td>
          <td style="background-color: white;">' . $data['existingUsers']['total'] . '</td>
          <td>' . $data['newUsers'] . ' (' . $data['percentNewUsers'] . '% new)</td>
          <td style="background-color: ' . $data['budgetBackgroundColor'] . '; color: black;">' . $data['budgetPercentage'] . '</td>
        </tr>' . PHP_EOL;
      $projected = '<p>' . $data['budgetProjectedCompletion'] . '</p>';

    }
    $reportContents .= '</table>' . PHP_EOL;

    $report = $reportTitle . $reportContents . $projected;
    return $report;
  }

  /**
   * Compose the contents of the existing users import report content for Slack.
   *
   * @param stats array
   *   Details of the user accounts that existed in Mailchimp, Mobile Common
   *   and/or Drupal at the time of import.
   *
   * @return string
   *   The text to be displayed in the report.
   */
  private function composedReportMarkupSlack($reportData) {

    $attachments = [];

    foreach ($reportData as $source => $data) {

      $reportRange = $data['userImportCSV']['startDate'] . ' - ' . $data['userImportCSV']['endDate'];

      if ($source == 'niche') {

        $reportData = [
          'color' => $data['budgetBackgroundColor'],
          'fallback' => 'User Import Daily Report: Niche.com',
          'author_name' => 'Niche.com',
          'author_icon' => 'http://static.tumblr.com/25dcac672bf20a1223baed360c75c453/mrlvgra/Jxhmu09gi/tumblr_static_niche-tumblr-logo.png',
          'title' => date('F', strtotime($data['userImportCSV']['startDate'])) . ' User Imports: ' . $reportRange,
          'title_link' => 'https://www.stathat.com/v/stats/576l/tf/1M1h',
          'text' => $data['budgetProjectedCompletion']
        ];
      }
      elseif ($source == 'afterschool') {

        $reportData = [
          'color' => $data['budgetBackgroundColor'],
          'fallback' => 'User Import Daily Report: After School',
          'author_name' => 'After School',
          'author_icon' => 'http://a4.mzstatic.com/us/r30/Purple69/v4/f7/43/fc/f743fc64-0cc6-171d-2f86-8649b5d3a8e1/icon175x175.jpeg',
          'title' => 'Planet Zombie User Imports: ' . $reportRange,
          'title_link' => 'https://www.stathat.com/v/stats/7CNJ/tf/1M1h'
        ];
      }

      // Common between all sources - the numbers
      $reportData['fields'] = [
        0 => [
          'title' => 'Users Processed',
          'value' => $data['userImportCSV']['usersProcessed'] ,
          'short' => true
        ],
        1 => [
          'title' => 'Existing Users',
          'value' => $data['existingUsers']['total'],
          'short' => true
        ],
        2 => [
          'title' => 'New Users',
          'value' => $data['newUsers'] . ' (' . $data['percentNewUsers'] .'% new)',
          'short' => true
        ],
        3 => [
          'title' => 'Budget',
          'value' => $data['budgetPercentage'],
          'short' => true
        ]
      ];

      // $attachments[] = $reportData;
      $attachments = $reportData;
    }

    return $attachments;
  }

  /**
   * Compose the contents of the existing users import report content.
   *
   * @param string $type
   *   The type of report defines who will get.
   * @param array $sources
   *
   * @return array
   *   The values for the various forms of reporting.
   *
   */
  private function getRecipients($type, $sources) {

    $recipients = null;

    if ($type = 'runningMonth') {

      $recipients['afterschool'] = [
        'OK' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ]
          ],
          'slack' => [
            '#quicksilver',
            '#after-school-internal'
          ]
        ],
        'Warning' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ]
          ],
          'slack' => [
            '#after-school-internal',
            '#quicksilver'
          ]
        ],
        'Alert' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ]
          ],
          'slack' => [
            '#after-school-internal',
            '#quicksilver',
            '@dee',
            '@fantini'
          ]
        ]
      ];

      $recipients['niche'] = [
        'OK' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ]
          ],
          'slack' => [
            '#quicksilver',
            '#niche_monitoring'
          ]
        ],
        'Warning' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ],
            [
              'address' => 'mranalli@dosomething.org',
              'name' => 'Marissa'
            ]
          ],
          'slack' => [
            '#quicksilver',
            '#niche_monitoring',
            '@dee'
          ]
        ],
        'Alert' => [
          'email' => [
            [
              'address' => 'dlee@dosomething.org',
              'name' => 'Dee'
            ],
            [
              'address' => 'mranalli@dosomething.org',
              'name' => 'Marissa'
            ],
            [
              'address' => 'mike@niche.com',
              'name' => 'Mike'
            ]
          ],
          'slack' => [
            '#quicksilver',
            '#niche_monitoring',
            '@dee',
            '@marissaranalli'
          ]
        ]
      ];

    }

    if (count($recipients) == 0) {
      throw new Exception('getRecipients() did not generate $recipients entries.');
    }

    return $recipients;
  }

  /**
   * Send email report to appropriate managers.
   *
   * @param string $source
   *   The name of the source of the user import data.
   * @param string $existingUsersReport string
   *   Details of the summary log entries for each import batch.
   * @param array $recipients
   *   A list of users to send the report to.
   * @param string $status
   *   The status of the import process relative to the budget for the source.
   */
  private function dispatchEmailReport($source, $composedReport, $recipients, $status = 'OK') {
    
    $memberCount = $this->mbToolbox->getDSMemberCount();

    foreach ($recipients as $sourceTo => $to) {

      if (isset($to[$status]['email']) && $sourceTo == $source) {

        foreach ($to[$status]['email'] as $email) {

          $message = array(
            'from_email' => 'machines@dosomething.org',
            'email' => $email['address'],
            'activity' => 'mb-reports',
            'email_template' => 'mb-user-import-report',
            'user_country' => 'US',
            'merge_vars' => array(
              'FNAME' => $email['name'],
              'SUBJECT' => 'Monthly User Import to Date Report - ' . date('Y-m-d'),
              'TITLE' => 'Monthly User Imports to Date',
              'SUBJECT' => 'Daily User Import Report - ' . date('Y-m-d'),
              'TITLE' => 'Daily User Imports',
              'BODY' => $composedReport,
              'MEMBER_COUNT' => $memberCount,
            ),
            'email_tags' => array(
              0 => 'mb-user-import-report',
            )
          );
          $payload = json_encode($message);
          $this->messageBroker->publish($payload, 'report.userimport.transactional', 1);
        }
      }
    }

  }

  /**
   * Send message to Slack user and/or channel.
   *
   * @param array attachment
   *   Formatted message settings based on SlackAPI: https://api.slack.com/docs/formatting
   * @param array $recipients
   *   List of Slack user names and/or channels.
   */
  private function dispatchSlackAlert($source, $attachment, $recipients, $status) {

    $channelNames = [];
    $tos = [];

    foreach ($recipients as $sourceTo => $to) {
      if (isset($to[$status]['slack']) && $sourceTo == $source) {
        foreach ($to[$status]['slack'] as $recipient) {
          if (strpos($recipient, '#') !== false) {
            $channelNames[] = $recipient;
          }
          // Users
          if (strpos($recipient, '@') !== false) {
            $tos[] = $recipient;
          }
          $this->slack->alert($channelNames, $attachment, $tos);
        }
      }
    }
  }

  /**
   * setBugetColor() - Based on the number of new users processed, set a color value - green, yellow, red
   * to highlight the current number of imported users.
   *
   * @param string $budgetState
   *
   *
   * @return string $color
   *   The CSS background-color property, used in report generation.
   */
  private function setBudgetColor($budgetState) {

    if ($budgetState == 'OK') {
      // green
      $color = '#00FF00';
    }
    if ($budgetState == 'Warning') {
      // yellow
      $color = '#FFFF00';
    }
    if ($budgetState == 'Alert') {
      $color = '#FF0000';
    }
    return $color;
  }

  /**
   * getBudgetState() : Define budget state based on the percentage of the budget has been reached.
   *
   * @param integer $percentage
   *   The about the budget has been used.
   */
  private function getBudgetState($percentage) {

    if ($percentage == 'Unlimited') {
      return 'OK';
    }

    if ($percentage <= 80) {
      $budgetState = 'OK';
    }
    if ($percentage > 80) {
      $budgetState = 'Warning';
    }
    if ($percentage >= 100) {
      $budgetState = 'Alert';
    }
    return $budgetState;
  }

  /**
   * budgetStatus() : Calculate the current budget status based on the budget for a source and amount
   * (users) the budget has been fulled.
   *
   * @param string $source
   *   The source of the users. Each source has it's own budget.
   * @param string $type
   *   Unused?!?
   * @param integer $newUsers
   *   The number of new users that have been creat in a budget period.
   */
  private function budgetStatus($source, $type, $newUsers) {

    if ($source == 'niche') {

      $currentMonth = date('n');
      $budgetPercentage = 100 - (self::NICHE_USER_BUDGET[$currentMonth] - $newUsers) / self::NICHE_USER_BUDGET[$currentMonth] * 100;
      $budgetPercentage = 100 - (self::NICHE_USER_BUDGET - $newUsers) / self::NICHE_USER_BUDGET * 100;
      $status['budgetPercentage'] = round($budgetPercentage, 1) . '%';
      $status['budgetState'] = $this->getBudgetState($status['budgetPercentage']);
      $status['budgetBackgroundColor'] = $this->setBudgetColor($status['budgetState'] );

      $averageDailyNewUsers = $newUsers / date('j');
      $projectedDaysInMonthToComplete = self::NICHE_USER_BUDGET / $averageDailyNewUsers;
      if (round($projectedDaysInMonthToComplete, 0) > date('t')) {
        $status['budgetProjectedCompletion'] = '** Projected new user rate will not complete budget.';
      }
      else {
        $status['budgetProjectedCompletion'] = '** Projected budget completion: ' . date('F') . ' ' . round($projectedDaysInMonthToComplete, 0) . ', ' . date('Y');
      }
    }
    if ($source == 'afterschool') {
      $status['budgetPercentage'] = self::AFTERSCHOOL_USER_BUDGET;
      $status['budgetState'] = $this->getBudgetState($status['budgetPercentage']);
      $status['budgetBackgroundColor'] = $this->setBudgetColor($status['budgetState']);
      $status['budgetProjectedCompletion'] = '';
    }

    return $status;
  }

}
