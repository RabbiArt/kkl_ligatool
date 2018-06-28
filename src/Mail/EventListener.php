<?php

namespace KKL\Ligatool\Mail;

use KKL\Ligatool\DB;
use KKL\Ligatool\Events;
use KKL\Ligatool\Template;

class EventListener {
  
  public function init() {
    Events\Service::registerCallback(Events\Service::$NEW_GAMEDAY_UPCOMING, array($this, 'send_reminder_mail'));
    Events\Service::registerCallback(Events\Service::$MATCH_FIXTURE_SET, array($this, 'post_new_fixture'));
  }
  
  public function post_new_fixture(Events\MatchFixtureUpdatedEvent $event) {
    $db = new DB\Wordpress();
    $match = $event->getMatch();
    $home = $db->getTeam($match->home_team);
    $away = $db->getTeam($match->away_team);
    $league = $db->getLeagueForGameday($match->game_day_id);
    $time = strtotime($match->fixture);
    setlocale(LC_TIME, 'de_DE');
    $text = strftime("%d. %B %Y %H:%M:%S", $time);
    if($match->location) {
      $location = $db->getLocation($match->location);
      if($location) {
        $text .= ", Spielort: " . $location->title;
      }
    }
    
    $actor = $db->getPlayerByMailAddress($event->getActorEmail());
    if(!$actor) {
      $actor = $event->getActorEmail();
    } else {
      $actor = $actor->first_name . " " . $actor->last_name;
    }
    
    $data = array("mail" => array("to" => array("name" => "",), "actor" => array("name" => $actor,),), "match" => array("text" => $league->name . ': ' . $home->name . ' gegen ' . $away->name, "fixture" => $text,),);
    
    $data['mail']['to']['name'] = 'Ligaleitung';
    $this->sendMail('stephan-alleine@undev.de', null, $data);
    $data['mail']['to']['name'] = $home->name;
    $this->sendMail('stephan@5711.org', 'stephan@undev.de', $data);
    $data['mail']['to']['name'] = $away->name;
    $this->sendMail('stephan@undev.de', 'stephan@5711.org', $data);
    
  }
  
  public function send_reminder_mail(Events\GameDayReminderEvent $event) {
    
    $to = "Kölner Kickerliga <ligaleitung@kickerligakoeln.de>";
    $subject = "Anstehende Spiele in der Kölner Kickerliga";
    $headers = array(
      'From: Ligaleitung <ligaleitung@kickerligakoeln.de>',
      'Reply-To: ligaleitung@kickerligakoeln.de',
      'MIME-Version: 1.0',
      'Content-type: text/html; charset=utf-8'
    );
  
    $templateEngine = Template\Service::getTemplateEngine();
    $template = $templateEngine->loadTemplate('mails/reminder_mail.twig');
    $message = $template->render(array('matches' => $event->getMatches()));
    wp_mail($to, $subject, $message, $headers);
    
  }
  
  private function sendMail($to, $cc, $data) {
    
    $kkl_twig = Template\Service::getTemplateEngine();
    
    $to = $data['mail']['to']['name'] . "<" . $to . ">";
    $subject = '[kkl] Spieltermin ' . $data['match']['text'];
    $headers = array(
      'From: Ligaleitung <ligaleitung@kickerligakoeln.de>',
      'Reply-To: ligaleitung@kickerligakoeln.de',
      'MIME-Version: 1.0',
      'Content-type: text/html; charset=utf-8'
    );
    if($cc != null) {
      $headers[] = 'Cc: ' . $cc;
    }
    
    $message = $kkl_twig->render('mails/new_fixture_mail.twig', $data);
    wp_mail($to, $subject, $message, $headers);
  }
  
}
