<?php


namespace KKL\Ligatool\Shortcode;

use KKL\Ligatool\Plugin;
use KKL\Ligatool\ServiceBroker;
use KKL\Ligatool\Template;

class GameDayTable extends Shortcode {

  public static function render($atts, $content, $tag) {
    $kkl_twig = Template\Service::getTemplateEngine();

    $scheduleService = ServiceBroker::getScheduleService();
    $clubService = ServiceBroker::getClubService();
    $teamService = ServiceBroker::getTeamService();

    $context = Plugin::getUrlContext();
    $schedules = array();
    if ($context->getLeague() && $context->getSeason() && $context->getGameDay()) {

      $league = $context->getLeague();
      $season = $context->getSeason();
      $day = $context->getGameDay();

      $schedule = $scheduleService->getScheduleForGameDay($day);
      foreach ($schedule->getMatches() as $match) {
        $home_team = $teamService->byId($match->getHomeTeam());
        $away_team = $teamService->byId($match->getAwayTeam());
        $home_club = $clubService->byId($home_team->getClubId());
        $away_club = $clubService->byId($away_team->getClubId());
        // TODO: use some kind of DTO
        $match->home->link = Plugin::getLink('club', array('club' => $home_club->getShortName()));
        $match->away->link = Plugin::getLink('club', array('club' => $away_club->getShortName()));
      }
      $schedule->link = Plugin::getLink(
        'schedule',
        array('league' => $league->getCode(), 'season' => date('Y', strtotime($season->getStartDate())))
      );

      $schedules[] = $schedule;
    }

    return $kkl_twig->render(
      self::$TEMPLATE_PATH . '/ranking.twig',
      array(
        'context' => $context,
        'schedules' => $schedules,
        'view' => 'current'
      )
    );
  }
}