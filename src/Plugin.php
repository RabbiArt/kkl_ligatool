<?php

namespace KKL\Ligatool;

use KKL\Ligatool\Widget\OtherLeagues;
use KKL\Ligatool\Widget\OtherSeasons;
use KKL\Ligatool\Widget\UpcomingGames;
use scbLoad4;

class Plugin {
  
  private static $baseUrl;
  private static $basePath;
  private static $context = array();
  private static $pluginFile;
  
  private $db;
  
  public function __construct($pluginFile, $baseUrl, $basePath) {
    static::$pluginFile = $pluginFile;
    static::$baseUrl = $baseUrl;
    static::$basePath = $baseUrl;
    $this->db = new DB\Wordpress();
  }
  
  public static function getContext() {
    return self::$context;
  }
  
  public static function setContext($context) {
    self::$context = $context;
  }
  
  /**
   * @return mixed
   */
  public static function getBaseUrl() {
    return self::$baseUrl;
  }
  
  /**
   * @return mixed
   */
  public static function getBasePath() {
    return self::$basePath;
  }
  
  /**
   * @return mixed
   */
  public static function getPluginFile() {
    return self::$pluginFile;
  }
  
  public static function getLink($type, $values) {
    
    $link = "";
    
    if($type == "club") {
      $link = $values['club'];
      $link .= "/";
    }
    
    if($type == "league") {
      $link = $values['league'];
      if($values['season'])
        $link .= "/" . $values['season'];
      if($values['game_day'])
        $link .= "/" . $values['game_day'];
      $link .= "/";
    }
    
    if($type == "schedule") {
      $link = $values['league'] . "/" . $values['season'] . "/";
      if($values['team'])
        $link .= "?team=" . $values['team'];
    }
    
    return $link;
    
  }
  
  public function getDB() {
    return $this->db;
  }
  
  public function init() {
  
    add_option(DB\Wordpress::$VERSION_KEY, DB\Wordpress::$VERSION );
  
    register_activation_hook( static::getPluginFile(), array($this->getDB(), 'installWordpressDatabase'));
    register_activation_hook( static::getPluginFile(), array($this->getDB(), 'installWordpressData'));
    
    add_filter('query_vars', array($this, 'add_query_vars_filter'));
    
    add_action('init', array($this, 'add_rewrite_rules'));

    add_shortcode('koelncup_table', array(KoelnClub::class, 'renderTable'));
    add_shortcode('koelncup_games', array(KoelnClub::class, 'renderGames'));
    
    add_shortcode('league_table', array(Shortcodes::class, 'leagueTable'));
    add_shortcode('table_overview', array(Shortcodes::class, 'tableOverview'));
    add_shortcode('gameday_table', array(Shortcodes::class, 'gameDayTable'));
    add_shortcode('gameday_overview', array(Shortcodes::class, 'gameDayOverview'));
    add_shortcode('league_overview', array(Shortcodes::class, 'leagueOverview'));
    add_shortcode('club_detail', array(Shortcodes::class, 'clubDetail'));
    add_shortcode('gameday_pager', array(Shortcodes::class, 'gameDayPager'));
    add_shortcode('season_schedule', array(Shortcodes::class, 'seasonSchedule'));
    add_shortcode('contact_list', array(Shortcodes::class, 'contactList'));
    add_shortcode('set_match_fixture', array(Shortcodes::class, 'setMatchFixture'));

    add_action('widgets_init', array($this, 'register_widgets'));

    register_sidebar(array('name' => __(__('kkl_global_sidebar', 'kkl-ligatool')), 'id' => 'kkl_global_sidebar', 'description' => __('Widgets in this area will be shown on pages using any kkl page template below the page sidebar.'), 'class' => 'kkl_global_sidebar', 'before_title' => '<h4>', 'after_title' => '</h4>',));
    register_sidebar(array('name' => __(__('kkl_table_sidebar', 'kkl-ligatool')), 'id' => 'kkl_table_sidebar', 'description' => __('Widgets in this area will be shown on pages using the table page template.'), 'class' => 'kkl_table_sidebar', 'before_title' => '<h4>', 'after_title' => '</h4>',));
    register_sidebar(array('name' => __(__('kkl_leagues_sidebar', 'kkl-ligatool')), 'id' => 'kkl_leagues_sidebar', 'description' => __('Widgets in this area will be shown on pages using the league page template.'), 'class' => 'kkl_league_sidebar', 'before_title' => '<h4>', 'after_title' => '</h4>',));
    register_sidebar(array('name' => __(__('kkl_schedule_sidebar', 'kkl-ligatool')), 'id' => 'kkl_schedule_sidebar', 'description' => __('Widgets in this area will be shown on pages using the schedule page template.'), 'class' => 'kkl_schedule_sidebar', 'before_title' => '<h4>', 'after_title' => '</h4>',));
    register_sidebar(array('name' => __(__('kkl_teamdetail_sidebar', 'kkl-ligatool')), 'id' => 'kkl_teamdetail_sidebar', 'description' => __('Widgets in this area will be shown on pages using the team page template.'), 'class' => 'kkl_teamdetail_sidebar', 'before_title' => '<h4>', 'after_title' => '</h4>',));
    
    $slackIntegration = new Slack\EventListener();
    $slackIntegration->init();
    
    $mailIntegration = new Mail\EventListener();
    $mailIntegration->init();
    
    Api\Service::init();
    Tasks\Service::init();
    
    if(is_admin()) {
      new Updater(__FILE__, 'steam0r', 'kkl_ligatool');
      scbLoad4::init(function() {
        $options = array();
        new Backend\LeagueAdminPage(__FILE__, $options);
        new Backend\ClubAdminPage(__FILE__, $options);
        new Backend\GameDayAdminPage(__FILE__, $options);
        new Backend\MatchAdminPage(__FILE__, $options);
        new Backend\SeasonAdminPage(__FILE__, $options);
        new Backend\TeamAdminPage(__FILE__, $options);
        new Backend\LocationAdminPage(__FILE__, $options);
        new Backend\PlayerAdminPage(__FILE__, $options);
      });
      add_filter('set-screen-option', array('KKLBackend', 'set_screen_options'), 10, 3);
      Backend::display();
    }else{
      static::enqueue_scripts(
        array(array('handle' => 'kkl_frontend_datepicker', 'src' => 'jquery.datetimepicker.js', 'type' => 'js'),
              array('handle' => 'kkl_frontend_datepicker', 'src' => 'jquery.datetimepicker.css', 'type' => 'css'),
              array('handle' => 'kkl_set_fixture', 'src' => 'frontend/set_fixture.js', 'type' => 'js')
        ));
    }
    
    add_action('plugins_loaded', function() {
      load_plugin_textdomain('kkl-ligatool', false, dirname(plugin_basename(__FILE__)) . '/../lang/');
    });
  }
  
  public static function enqueue_scripts($arr) {
    foreach($arr as $script) {
      if($script['type'] === 'js') {
        $src = plugins_url() . '/kkl_ligatool/js/' . $script['src'];
        wp_register_script(
          $script['handle'], $src, array(), false, true
        );
        wp_enqueue_script($script['handle']);
      } elseif($script['type'] === 'css') {
        $str = plugins_url() . '/kkl_ligatool/css/' . $script['src'];
        wp_enqueue_style($script['handle'], $str);
      }
    }
  }
  
  public function add_query_vars_filter($vars) {
    $vars[] = "league";
    $vars[] = "season";
    $vars[] = "game_day";
    $vars[] = "match";
    $vars[] = "club";
    $vars[] = "team";
    $vars[] = "json";
    
    return $vars;
  }

  public function register_widgets() {
    register_widget(new OtherLeagues());
    register_widget(new OtherSeasons());
    register_widget(new UpcomingGames());
  }

  public function add_rewrite_rules() {
    
    $pages = get_pages(array('meta_key' => '_wp_page_template', 'meta_value' => 'page-ranking.php',));
    
    foreach($pages as $page) {
      $url_endpoint = get_permalink($page->ID);
      $url_endpoint = parse_url($url_endpoint);
      $url_endpoint = ltrim($url_endpoint['path'], '/');
      
      $rule = '^' . $url_endpoint . '([^/]*)?/?([^/]*)?/?([^/]*)?/?';
      $page = 'index.php?page_id=' . $page->ID . '&league=$matches[1]&season=$matches[2]&game_day=$matches[3]';
      add_rewrite_rule($rule, $page, 'top');
    }
    
    $pages = get_pages(array('meta_key' => '_wp_page_template', 'meta_value' => 'page-club.php',));
    
    foreach($pages as $page) {
      $url_endpoint = get_permalink($page->ID);
      $url_endpoint = parse_url($url_endpoint);
      $url_endpoint = ltrim($url_endpoint['path'], '/');
      
      $rule = '^' . $url_endpoint . '([^/]*)?/?';
      $page = 'index.php?page_id=' . $page->ID . '&club=$matches[1]';
      add_rewrite_rule($rule, $page, 'top');
    }
    
    $pages = get_pages(array('meta_key' => '_wp_page_template', 'meta_value' => 'page-schedule.php',));
    
    foreach($pages as $page) {
      $url_endpoint = get_permalink($page->ID);
      $url_endpoint = parse_url($url_endpoint);
      $url_endpoint = ltrim($url_endpoint['path'], '/');
      
      $rule = '^' . $url_endpoint . '([^/]*)?/?([^/]*)?/?';
      $page = 'index.php?page_id=' . $page->ID . '&league=$matches[1]&season=$matches[2]';
      add_rewrite_rule($rule, $page, 'top');
    }
    
    flush_rewrite_rules(true);
    
  }
  
  public function getContextByLeagueAndSeasonAndGameDay($league, $season, $game_day) {
    
    $data = array();
    
    $league = $this->db->getLeagueBySlug($league);
    $season = $this->db->getSeasonByLeagueAndYear($league->id, $season);
    $game_day = $this->db->getGameDayBySeasonAndPosition($season->id, $game_day);
    
    $data['league'] = $league;
    $data['season'] = $season;
    $data['game_day'] = $game_day;
    
    return $data;
  }
  
  public function getContextByLeagueAndSeason($league, $season) {
    $data = array();
    
    $league = $this->db->getLeagueBySlug($league);
    $season = $this->db->getSeasonByLeagueAndYear($league->id, $season);
    $game_day = $this->db->getGameDay($season->current_game_day);
    
    $data['league'] = $league;
    $data['season'] = $season;
    $data['game_day'] = $game_day;
    
    return $data;
  }
  
  public function getContextByLeague($league) {
    
    $data = array();
    
    $league = $this->db->getLeagueBySlug($league);
    $season = $this->db->getSeason($league->current_season);
    $game_day = $this->db->getGameDay($season->current_game_day);
    
    $data['league'] = $league;
    $data['season'] = $season;
    $data['game_day'] = $game_day;
    
    return $data;
  }
  
  public function getContextByClubCode($clubCode) {
    
    $data = array();
    $data['club'] = $this->db->getClubByCode($clubCode);
    
    return $data;
    
  }
  
}
