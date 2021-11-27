<?php


namespace KKL\Ligatool\Services;


use Closure;
use KKL\Ligatool\DB\Where;
use KKL\Ligatool\Model\Match;
use KKL\Ligatool\Model\Rank;
use KKL\Ligatool\Model\Ranking;
use KKL\Ligatool\Model\Team;
use KKL\Ligatool\Model\TeamScore;
use KKL\Ligatool\ServiceBroker;

class RankingService {

  public static $RANKING_MODEL_SCORE_GAME_DIFF = 0;

  public function getRankingModelForSeason($seasonId) {
    // TODO: maybe store this in database on a per season base, to make other sorting possible
    return static::$RANKING_MODEL_SCORE_GAME_DIFF;
  }

  /**
   * @param $model int
   * @return Closure
   */
  public function getRankingModelSortingFunction($model) {
    switch ($model) {
      case static::$RANKING_MODEL_SCORE_GAME_DIFF:
      default:
        $sortingFunction = function (Rank $first, Rank $second) {
	        if ($first->getScore() == $second->getScore()) {
		        if ($first->getGameDiff() == $second->getGameDiff()) {
			        return 1;
		        }
		        return ($first->getGameDiff() > $second->getGameDiff()) ? -1 : 1;
	        }
	        return ($first->getScore() > $second->getScore()) ? -1 : 1;
        };
        break;
    }
    return $sortingFunction;

  }

  /**
   * @param $leagueId
   * @param $seasonId
   * @param $dayNumber
   * @param bool $live
   * @return Ranking
   */
  public function getRankingForLeagueAndSeasonAndGameDay($leagueId, $seasonId, $dayNumber, $live = false) {

    $ranking = new Ranking();

    $seasonService = ServiceBroker::getSeasonService();
    $leagueService = ServiceBroker::getLeagueService();
    $teamService = ServiceBroker::getTeamService();
    $gameDayService = ServiceBroker::getGameDayService();
    $teamScoreService = ServiceBroker::getTeamScoreService();

    $season = $seasonService->byId($seasonId);
    $league = $leagueService->byId($leagueId);
    $day = $gameDayService->bySeasonAndPosition($seasonId, $dayNumber);

    $ranking->setLeague($league);
    $ranking->setSeason($season);
    $ranking->setGameDay($day);

    $teams = $teamService->forSeason($seasonId);

    $ranks = [];
    foreach ($teams as $team) {
      $scores = $teamScoreService->forTeamUntilGameDay($team, $day);
      $scoreSum = 0;
      $wins = 0;
      $losses = 0;
      $draws = 0;
      $goalsFor = 0;
      $goalsAgainst = 0;
      $gamesFor = 0;
      $gamesAgainst = 0;
      foreach ($scores as $score) {
        $scoreSum += $score->getScore();
        $wins += $score->getWin() ? 1 : 0;
        $losses += $score->getLoss() ? 1 : 0;
        $draws += $score->getDraw() ? 1 : 0;
        $goalsFor += $score->getGoalsFor();
        $goalsAgainst += $score->getGoalsAgainst();
        $gamesFor += $score->getGamesFor();
        $gamesAgainst += $score->getGamesAgainst();
        $gamesFor += $score->getGamesFor();
      }
      $rank = new Rank();
      $rank->setTeamId($team->getId());
      $rank->setScore($scoreSum);
      $rank->setWins($wins);
      $rank->setLosses($losses);
      $rank->setDraws($draws);
      $rank->setGoalsFor($goalsFor);
      $rank->setGoalsAgainst($goalsAgainst);
      $rank->setGamesFor($gamesFor);
      $rank->setGamesAgainst($gamesAgainst);
      $rank->setGameDiff($gamesFor - $gamesAgainst);
      $rank->setRunning(false);
      $ranks[] = $rank;
    }

    if ($live) {
      $ranks = $this->addLiveScores($ranks, $dayNumber);
    }

    $sortingFunction = $this->getRankingModelSortingFunction($this->getRankingModelForSeason($day->getSeasonId()));
	uasort($ranks, $sortingFunction);

    $original_size = count($ranks);
    $teams = $teamService->forSeason($seasonId);
    if ($original_size < count($teams)) {
      // find place where to insert scores
      if ($original_size > 0) {
        for ($i = 0; $i < $original_size; $i++) {
          $score = $ranks[$i];
          // punkte = 0 oder weniger
          // UND differenz weniger als null ODER differenz gleich 0 und geschossene tore weniger als null
          if ($score->getScore() <= 0 && (($score->getGoalDiff() < 0) || ($score->getGoalDiff() == 0 && $score->getGoalsFor() <= 0))) {
            foreach ($teams as $team) {
              $has_score = false;
              foreach ($ranks as $iscore) {
                if ($team->getId() == $iscore->getTeamId())
                  $has_score = true;
              }
              if (!$has_score) {
                $new_score = new Rank();
                $new_score->setTeamId($team->getId());
                $new_score->setWins(0);
                $new_score->setDraws(0);
                $new_score->setLosses(0);
                $new_score->setGoalsFor(0);
                $new_score->setGoalsAgainst(0);
                $new_score->setGoalDiff(0);
                $new_score->setGamesFor(0);
                $new_score->setGamesAgainst(0);
                $new_score->setGameDiff(0);
                $new_score->setScore(0);
                $ranks[] = $new_score;
              }
            }
          } elseif ($original_size == ($i + 1)) {
            // last element, add scores here
            foreach ($teams as $team) {
              $has_score = false;
              foreach ($ranks as $iscore) {
                if ($team->getId() == $iscore->getTeamId())
                  $has_score = true;
              }
              if (!$has_score) {
                $new_score = new Rank();
                $new_score->setTeamId($team->getId());
                $new_score->setWins(0);
                $new_score->setDraws(0);
                $new_score->setLosses(0);
                $new_score->setGoalsFor(0);
                $new_score->setGoalsAgainst(0);
                $new_score->setGoalDiff(0);
                $new_score->setGamesFor(0);
                $new_score->setGamesAgainst(0);
                $new_score->setGameDiff(0);
                $new_score->setScore(0);
                $ranks[] = $new_score;
              }
            }
          }
        }
      } else {
        // no scores at all, fake everything
        foreach ($teams as $team) {
          $has_score = false;
          foreach ($ranks as $iscore) {
            if ($team->getId() == $iscore->getTeamId())
              $has_score = true;
          }
          if (!$has_score) {
            $new_score = new Rank();
            $new_score->setTeamId($team->getId());
            $new_score->setWins(0);
            $new_score->setDraws(0);
            $new_score->setLosses(0);
            $new_score->setGoalsFor(0);
            $new_score->setGoalsAgainst(0);
            $new_score->setGoalDiff(0);
            $new_score->setGamesFor(0);
            $new_score->setGamesAgainst(0);
            $new_score->setGameDiff(0);
            $new_score->setScore(0);
            $ranks[] = $new_score;
          }
        }
      }
    }

    $position = 0;
    $previousScore = 0;
    $previousGameDiff = 0;
    foreach ($ranks as $rank) {

      $position++;

      $rank->team = $teamService->byId($rank->getTeamId());
      $rank->games = $rank->getWins() + $rank->getLosses() + $rank->getDraws();

      if (($previousScore == $rank->getScore()) && ($previousGameDiff == $rank->getGameDiff())) {
        $rank->shared_rank = true;
      }

      $previousScore = $rank->getScore();
      $previousGameDiff = $rank->getGameDiff();
      $rank->position = $position;

    }

    $ranking->setRanks($ranks);
    return $ranking;

  }

  /**
   * @param $ranking
   * @param $dayNumber
   * @return Rank[]
   */
  private function addLiveScores($ranking, $dayNumber) {

    $gameDayService = ServiceBroker::getGameDayService();
    $matchService = ServiceBroker::getMatchService();
    $teamService = ServiceBroker::getTeamService();
    $teamScoreService = ServiceBroker::getTeamScoreService();

    $day = $gameDayService->byId($dayNumber);
    $prevDay = $gameDayService->getPrevious($day);
    $matches = $matchService->byGameDay($day->getId());

    /**
     * @var TeamScore[]
     */
    $scores = array();
    foreach ($matches as $match) {
      $home = $teamService->byId($match->getHomeTeam());
      $away = $teamService->byId($match->getAwayTeam());
      $scores[$match->getHomeTeam()] = $this->getScoresForTeamAndMatch($match, $home);
      $scores[$match->getAwayTeam()] = $this->getScoresForTeamAndMatch($match, $away);
    }
    foreach ($scores as $teamId => $score) {
		$team = $teamService->byId($teamId);
      if (!$score->isFinal() && !($score->getGoalsFor() == 0 && $score->getGoalsAgainst() == 0)) {
        $scorePlus = 0;
        if ($score->getDraw()) {
          $scorePlus = 1;
        } elseif ($score->getWin()) {
          $scorePlus = 2;
        }
        $rank = new Rank();
        $rank->setTeamId($teamId);
        $rank->setRunning(true);
        if ($prevDay) {
          $prevScore = $teamScoreService->forTeamUntilGameDay($team, $prevDay);
          $rank->setScore($prevScore->getScore() + $scorePlus);
          $rank->setWins($prevScore->getScore() + $score->getWin());
          $rank->setLosses($prevScore->getScore() + $score->getLoss());
          $rank->setDraws($prevScore->getScore() + $score->getDraw());
          $rank->setGoalsFor($prevScore->getScore() + $score->getGoalsFor());
          $rank->setGoalsAgainst($prevScore->getScore() + $score->getGoalsAgainst());
          $rank->setGoalDiff($prevScore->getScore() + ($score->getGoalsAgainst() - $score->getGoalsAgainst()));
          $rank->setGamesFor($prevScore->getScore() + $score->getGamesFor());
          $rank->setGamesAgainst($prevScore->getScore() + $score->getGamesAgainst());
          $rank->setGameDiff($prevScore->getScore() + ($score->getGamesFor() - $score->getGamesAgainst()));
        } else {
          $rank->setScore($scorePlus);
          $rank->setWins($score->getWin());
          $rank->setLosses($score->getLoss());
          $rank->setDraws($score->getDraw());
          $rank->setGoalsFor($score->getGamesFor());
          $rank->setGoalsAgainst($score->getGoalsAgainst());
          $rank->setGoalDiff($score->getGamesFor() - $score->getGoalsAgainst());
          $rank->setGamesFor($score->getGamesFor());
          $rank->setGamesAgainst($score->getGamesAgainst());
          $rank->setGameDiff($score->getGamesFor() - $score->getGamesAgainst());
        }
        $ranking[] = $rank;
      }
    }

    return $ranking;
  }


  /**
   * @param $match Match
   * @param $team Team
   * @return TeamScore|null
   */
  private function getScoresForTeamAndMatch($match, $team) {

    $gameDayService = ServiceBroker::getGameDayService();
    $teamScoreService = ServiceBroker::getTeamScoreService();
    $scoringService = ServiceBroker::getScoringService();

    $day = $gameDayService->byId($match->getGameDayId());

    $score = $teamScoreService->findOne([
      new Where('gameDay_id', $day->getId()),
      new Where('team_id', $team->getId())
    ]);

    if ($score == null) {
      $score = new TeamScore();
      $score->setTeamId($team->getId());
      $score->setGameDayId($day->getId());
      $score->setFinal(false);
    } else {
      $score->setFinal(true);
    }

    $score->setWin(0);
    $score->setDraw(0);
    $score->setLoss(0);
    $score->setGamesAgainst(0);
    $score->setGamesFor(0);
    $score->setGoalsAgainst(0);
    $score->setGoalsFor(0);
    $score->setScore(0);

    if ($match->getHomeTeam() == $team->getId()) {
      $score->setGoalsFor($this->getGoalsForTeam($match, $match->getHomeTeam()));
      $score->setGoalsAgainst($this->getGoalsForTeam($match, $match->getAwayTeam()));
      $score->setGamesFor($match->getScoreHome());
      $score->setGamesAgainst($match->getScoreAway());
      if ($match->getScoreHome() > $match->getScoreAway()) {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$WIN));
        $score->setWin(1);
      } elseif ($match->getScoreHome() < $match->getScoreAway()) {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$LOSS));
        $score->setLoss(1);
      } else {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$DRAW));
        $score->setDraw(1);
      }
    }

    if ($match->getAwayTeam() == $team->getId()) {
      $score->setGoalsFor($this->getGoalsForTeam($match, $match->getAwayTeam()));
      $score->setGoalsAgainst($this->getGoalsForTeam($match, $match->getHomeTeam()));
      $score->setGamesFor($match->getScoreAway());
      $score->setGamesAgainst($match->getScoreHome());
      if ($match->getScoreHome() > $match->getScoreAway()) {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$LOSS));
        $score->setLoss(1);
      } elseif ($match->getScoreHome() < $match->getScoreAway()) {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$WIN));
        $score->setWin(1);
      } else {
        $score->setScore($scoringService->getPointsForMatchResult(ScoringService::$DRAW));
        $score->setDraw(1);
      }
    }

    return $score;
  }

  /**
   * @param $match Match
   * @param $team_id int
   * @return int
   */
  private function getGoalsForTeam($match, $team_id) {

    $setService = ServiceBroker::getSetService();
    $gameService = ServiceBroker::getGameService();

    $goalsAway = 0;
    $goalsHome = 0;
    $sets = $setService->byMatch($match);
    foreach ($sets as $set) {
      $games = $gameService->bySet($set);
      foreach ($games as $game) {
        $goalsAway += $game->getGoalsAway();
        $goalsHome += $game->getGoalsHome();
      }
    }

    if ($match->getHomeTeam() == $team_id) {
      return $goalsHome;
    } elseif ($match->getAwayTeam() == $team_id) {
      return $goalsAway;
    } else {
      return 0;
    }
  }

}
