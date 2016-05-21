<?php
/**
 *
 */

namespace DoSomething\MBC_TransactionalDigest;

use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use \Exception;
 
/**
 *
 */
class MB_Toolbox_Campaign
{

  /**
   * Singleton instance of application configuration settings.
   * @var object $mbConfig
   */
   private $mbConfig;

  /**
   * Singleton instance of class used to report usage statistics.
   * @var object $statHat
   */
   private $statHat;

  /**
   * A collection of tools used by all of the Message Broker applications.
   * @var object $mbToolbox
   */
   private $mbToolbox;

  /**
   * Campaign title.
   * @var string $title
   */
   public $title;

  /**
   * Needs public scope to allow making reference to campaign nid when assigning campaigns
   * to user objects.
   * @var integer $drupal_nid
   */
   public $drupal_nid;

  /**
   * A flag to determine if the campaign has "staff pick" status. Used for sorting of
   * campaigns in user digest messages.
   * @var boolean $is_staff_pick
   */
   public $is_staff_pick;

  /**
   * The url to the campaign web page on the DoSomething.org web site.
   * @var string $url
   */
   public $url;

  /**
   * Markup for the campaign cover image.
   * @var string $image_campaign_cover
   */
   public $image_campaign_cover;

  /**
   * Campaign text displayed in summary listings to encourage users to take up the
   * "call to action".
   * @var string $call_to_action
   */
   public $call_to_action;

  /**
   * The problem that will be addressed by doing the campaign. Used in descriptive text in
   * digest message campaign listings.
   * @var string $fact_problem
   */
   public $fact_problem;

  /**
   * The solution to the problem the campaign is trying to address.
   * @var string $fact_solution
   */
   public  $fact_solution;

  /**
   * Special message from campaign manager about the campaign. Presence of this messages overrides
   * all other campaign descriptive text.
   * @var string $latest_news
   */
   public $latest_news;

  /**
   * The title of the Tip on how to complete the campaign.
   * @var string $during_tip_header
   */
   public $during_tip_header;

  /**
   * The descriptive ip text.
   * @var string $during_tip
   */
   public $during_tip;

  /**
   * HTML markup settings for each of the support communication platforms.
   * @var array $markup
   */
   public $markup;

  /**
   * __construct(): Trigger populating values in Campaign object when object is created.
   *
   * @param integer $nid
   *   nid (Drupal node ID) of the campaign content item.
   */
  public function __construct($nid) {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->mbToolboxcURL = $this->mbConfig->getProperty('mbToolboxcURL');
    
    $this->add($nid);
  }

  /**
   * Populate object properties based on campaign lookup on Drupal site.
   *
   * @param integer $nid
   *   The node ID (nid), a unique identifier for the content defined by the Drupal website.
   *   A campaign has a unique nid.
   */
  private function add($nid) {

    $campaignSettings = $this->gatherSettings($nid);

    $this->drupal_nid = $campaignSettings->nid;
    $googleAnalytics = '?utm_source=transactionalDigest&utm_medium=email&utm_campaign=start20160520';
    $this->url = 'http://www.dosomething.org/us/node/' . $campaignSettings->nid . $googleAnalytics . '#prove';

    // Title - required
    if (isset($campaignSettings->title)) {
      $this->title = $campaignSettings->title;
    }
    else {
      echo '- MBC_DigestEmail_Campaign->add(): Campaign ' . $nid . ' title not set.', PHP_EOL;
      throw new Exception('Unable to create Campaign object : ' . $nid . ' title not set.');
    }
    // image_cover->src - required
    if (isset($campaignSettings->image_cover->src)) {
      $this->image_campaign_cover = $campaignSettings->image_cover->src;
    }
    else {
      echo '- MBC_DigestEmail_Campaign->add(): Campaign ' . $nid . ' (' . $this->title . ') image_cover->src not set.', PHP_EOL;
      throw new Exception('Unable to create Campaign object : ' . $nid . ' (' . $this->title . ') image_cover->src not set.');
    }
    // call_to_action - nice to have but not a show stopper
    if (isset($campaignSettings->call_to_action)) {
      $this->call_to_action = trim($campaignSettings->call_to_action);
    }
    else {
      $this->call_to_action = '';
      $this->campaignErrors[] = 'Campaign ' . $nid . ' (' . $this->title . ') call_to_action not set.';
      echo '- MBC_DigestEmail_Campaign->add(): Campaign ' . $nid . ' (' . $this->title . ') call_to_action not set.', PHP_EOL;
    }
    // DO IT: During Tip Header - step_pre[0]->header - nice to have but not a show stopper
    if (isset($campaignSettings->pre_step_header)) {
      $this->during_tip_header = trim($campaignSettings->pre_step_header);
    }
    else {
      $this->during_tip_header = '';
      $this->campaignErrors[] = 'Campaign ' . $nid . ' (' . $this->title . ') DO IT: During Tip Header, step_pre[0]->header not set.';
      echo '- MBC_DigestEmail_Campaign->add(): Campaign ' . $nid . ' (' . $this->title . ') DO IT: During Tip Header, step_pre[0]->header not set.', PHP_EOL;
    }
    // DO IT: During Tip Copy - step_pre[0]->copy - nice to have but not a show stopper
    if (isset($campaignSettings->pre_step_copy)) {
      $this->during_tip_copy = ': ' . trim(strip_tags($campaignSettings->pre_step_copy));
    }
    else {
      $this->during_tip_copy = '';
      $this->campaignErrors[] = 'Campaign ' . $nid . ' (' . $this->title . ') DO IT: During Tip Copy, step_pre[0]->copy not set.';
      echo '- MBC_DigestEmail_Campaign->add(): Campaign ' . $nid . ' (' . $this->title . ') DO IT: During Tip Copy, step_pre[0]->copy not set.', PHP_EOL;
    }

    // Optionals
    // is_staff_pick
    if (isset($campaignSettings->is_staff_pick)) {
      $this->is_staff_pick = $campaignSettings->is_staff_pick;
    }
    // latest_news_copy - replaces Tip copy if set.
    if (isset($campaignSettings->latest_news_copy)) {
      $this->latest_news = trim($campaignSettings->latest_news_copy);
    }
    // Status
    if (isset($campaignSettings->status)) {
      $this->status = $campaignSettings->status;
    }
  }

  /**
   * Gather campaign properties based on the campaign lookup on the Drupal site.
   *
   * @param integer $nid
   *   The Drupal nid (node ID) of the target campaign.
   *
   * @return object
   *   The returned results from the call to the campaign endpoint on the Drupal site.
   *   Return boolean FALSE if request is unsuccessful.
   */
  private function gatherSettings($nid) {

    $dsDrupalAPIConfig = $this->mbConfig->getProperty('ds_drupal_api_config');
    $curlUrl = $dsDrupalAPIConfig['host'];
    $port = isset($dsDrupalAPIConfig['port']) ? $dsDrupalAPIConfig['port'] : NULL;
    if ($port != 0 && is_numeric($port)) {
      $curlUrl .= ':' . (int) $port;
    }

    $campaignAPIUrl = $curlUrl . '/api/v1/content/' . $nid;
    $result = $this->mbToolboxcURL->curlGET($campaignAPIUrl);

    // Exclude campaigns that don't have details in Drupal API or "Access
    // denied" due to campaign no longer published
    if ($result[1] == 200 && is_object($result[0])) {
      return $result[0];
    }
    elseif ($result[1] == 200 && is_array($result[0])) {
      throw new Exception('Call to ' . $campaignAPIUrl . ' returned  200 with rejected response. nid: ' . $nid);
    }
    elseif ($result[1] == 403) {
      throw new Exception('Call to ' . $campaignAPIUrl . ' returned 403 and rejected response: ' . $result[0][0] . ' . nid: ' . $nid);
    }
    else {
      throw new Exception('Unable to call ' . $campaignAPIUrl . ' to get Campaign object: ' . $nid);
    }
  }

}
