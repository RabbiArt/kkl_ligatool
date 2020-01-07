<?php

namespace KKL\Ligatool\Slack;

use KKL\Ligatool\Events;
use KKL\Ligatool\ServiceBroker;
use tigokr\Slack\Slack;

class EventListener {

  private static $TEST_CHANNEL = '#test';
  private static $LEAGUE_CHANNEL = '#spielbetrieb';

  public function init() {
    Events\Service::registerCallback(Events\Service::$MATCH_FIXTURE_SET, array($this, 'post_new_fixture'));
    Events\Service::registerCallback(Events\Service::$NEW_GAMEDAY_UPCOMING, array($this, 'post_new_gameday'));

  }

  public function post_new_fixture(Events\MatchFixtureUpdatedEvent $event) {
    $slack = $this->getSlack();
    $match = $event->getMatch();
    $teamService = ServiceBroker::getTeamService();
    $leagueService = ServiceBroker::getLeagueService();

    $home = $teamService->byId($match->home_team);
    $away = $teamService->byId($match->away_team);
    $league = $leagueService->byGameDay($match->game_day_id);
    $attachment = array();
    $attachment['title'] = $league->getName() . ': ' . $home->getName() . ' gegen ' . $away->getName();
    $time = strtotime($match->fixture);
    setlocale(LC_TIME, 'de_DE');
    $text = strftime("%d. %B %Y %H:%M:%S", $time);
    if ($match->location) {
      $locationService = ServiceBroker::getLocationService();
      $location = $locationService->byId($match->getLocation());
      if ($location) {
        $text .= ", Spielort: " . $location->getTitle();
      }
    }
    $attachment['text'] = $text;
    $attachment['title_link'] = get_site_url() . '/wp-admin/admin.php?page=kkl_matches_admin_page&id=' . $match->ID;
    $attachment['color'] = "#FF0000";

    $slack->call('chat.postMessage', array("icon_emoji" => ":robot_face:", "username" => "Mr. Robot", "channel" => $this->getChannel(), "text" => $event->getActorEmail() . " hat gerade einen Spieltermin eingetragen", "attachments" => json_encode(array($attachment))));
  }

  private function getSlack() {
    $options = get_option('kkl_ligatool');
    return new Slack($options['slackid']);
  }

  public function post_new_gameday(Events\GameDayReminderEvent $event) {

    $topMatches = $event->getTopMatches();
    $attachments = array();
    $attachment['title'] = "@benedikt: Zeit die Erinnerungsmail zu verschicken!";
    $attachment['text'] = "In deinem Postfach sollte eine vorformatierte Mail liegen.";
    $attachment['color'] = "#FF0000";
    $attachments[] = $attachment;
    foreach ($topMatches as $leagueName => $topMatch) {
      $a = array();
      $title = "@" . $topMatch['contact'] . ": ";
      if ($topMatch['type'] == "top") {
        $title .= "Topspiel";
        $a['color'] = "#88AF7C";
      } else {
        $title .= "Abstiegskampf";
        $a['color'] = "#AB4D47";
      }
      $title .= " in " . $leagueName;
      $a['title'] = $title;
      $a['title_link'] = "https://www.kickerligakoeln.de/tabelle/" . $topMatch['leaguecode'] . "/";
      $a['text'] = $topMatch['home'] . " gegen " . $topMatch['away'];
      $attachments[] = $a;
    }
    $slack = $this->getSlack();
    $slack->call('chat.postMessage', array("icon_emoji" => ":robot_face:", "username" => "Mr. Robot", "channel" => $this->getChannel(), "text" => "Ein neuer Spieltag steht an:", "attachments" => json_encode($attachments)));

  }

  private function getChannel() {
    return static::$LEAGUE_CHANNEL;
  }

}
