<?php

namespace App\Services;


use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service class for predicting championship rates based on current standings and team performances.
 */
class PredictionService
{
    /**
     * Weight given to the home advantage in strength calculation.
     */
    private const HOME_ADVANTAGE_WEIGHT = 1.1;

    /**
     * Factor for adding randomness to match outcome prediction.
     */
    private const RANDOMNESS_FACTOR = 10;

    /**
     * Calculates the championship rates for each team.
     *
     * @return Collection A collection of teams with their respective championship rates.
     */
    public function calculateChampionshipRates(): Collection
    {
        $standings = $this->predictLeagueStandings();
        $totalPotentialPoints = $standings->sum('predicted_points');

        return $standings->map(function ($standing) use ($totalPotentialPoints) {
            $championshipRate = $totalPotentialPoints > 0 ? ($standing->predicted_points / $totalPotentialPoints) * 100 : 0;
            $formattedRate = number_format($championshipRate, 2);

            return [
                'team_name' => $standing->team->name,
                'championship_rate' => $formattedRate
            ];
        });
    }

    /**
     * Predicts the league standings based on current team points and potential points.
     *
     * @return Collection Predicted league standings.
     */
    private function predictLeagueStandings(): Collection
    {
        $standings = LeagueStandings::with('team')->get();

        foreach ($standings as $standing) {
            $potentialPoints = $this->calculatePotentialPoints($standing->team);
            $standing->predicted_points = $standing->points + $potentialPoints;
        }

        return $standings->sortByDesc('predicted_points');
    }

    /**
     * Calculates the potential points a team can earn from remaining matches.
     *
     * @param Team $team The team for which potential points are calculated.
     * @return int The calculated potential points.
     */
    private function calculatePotentialPoints(Team $team): int
    {
        $remainingMatches = $this->getRemainingMatchesForTeam($team->id);
        $potentialPoints = 0;

        foreach ($remainingMatches as $match) {
            $isHome = $match->home_team_id === $team->id;
            $opponentStrength = $this->getTeamStrength($isHome ? $match->away_team_id : $match->home_team_id);
            $potentialPoints += $this->estimateMatchOutcome($team->strength, $opponentStrength, $isHome, $team);
        }

        return $potentialPoints;
    }

    /**
     * Retrieves the remaining matches for a given team.
     *
     * @param int $teamId ID of the team.
     * @return Collection The remaining matches for the team.
     */
    private function getRemainingMatchesForTeam(int $teamId): Collection
    {
        return FootballMatch::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })->whereNull('home_score')->get();
    }

    /**
     * Retrieves the strength of a team.
     *
     * @param int $teamId ID of the team.
     * @return int The strength of the team.
     */
    private function getTeamStrength(int $teamId): int
    {
        return Team::find($teamId)->strength;
    }

    /**
     * Estimates the match outcome based on team strengths, home advantage, and historical performance.
     *
     * @param int $teamStrength Strength of the team.
     * @param int $opponentStrength Strength of the opponent team.
     * @param bool $isHome Indicates if the team is playing at home.
     * @param Team $team The team for which the match outcome is being estimated.
     * @return int Estimated points from the match.
     */
    private function estimateMatchOutcome(int $teamStrength, int $opponentStrength, bool $isHome, Team $team): int
    {
        $adjustedTeamStrength = $isHome ? $teamStrength * self::HOME_ADVANTAGE_WEIGHT : $teamStrength;
        $adjustedTeamStrength += $this->adjustPastMatches($team);

        $strengthDifference = $adjustedTeamStrength - $opponentStrength + rand(-self::RANDOMNESS_FACTOR, self::RANDOMNESS_FACTOR);

        if ($strengthDifference > 20) {
            return 3; // Win
        } elseif ($strengthDifference > -20) {
            return 1; // Draw
        } else {
            return 0; // Loss
        }
    }

    /**
     * Adjusts a team's strength based on its past matches.
     *
     * @param Team $team The team for which the strength is being adjusted.
     * @return float The adjusted strength based on past matches.
     */
    private function adjustPastMatches(Team $team): float
    {
        $pastMatches = [];
        foreach ($team->homeMatches as $match) {
            $pastMatches[] = $match;
        }
        foreach ($team->awayMatches as $match) {
            $pastMatches[] = $match;
        }

        $totalMatches = count($pastMatches);
        if ($totalMatches === 0) {
            return 0;
        }

        $totalWins = 0;
        $totalGoalsScored = 0;

        foreach ($pastMatches as $pastMatch) {
            if ($pastMatch->home_team_id == $team->id) {
                if ($pastMatch->home_score > $pastMatch->away_score) {
                    $totalWins++;
                }
                $totalGoalsScored += $pastMatch->home_score;
            }

            if ($pastMatch->away_team_id == $team->id) {
                if ($pastMatch->away_score > $pastMatch->home_score) {
                    $totalWins++;
                }
                $totalGoalsScored += $pastMatch->away_score;
            }
        }

        $winRate = $totalWins / $totalMatches;
        $averageGoalsScored = $totalGoalsScored / $totalMatches;

        $winRateWeight = 2.0;
        $goalsScoredWeight = 1.0;

        $historicalStrength =
            $winRate * $winRateWeight + $averageGoalsScored * $goalsScoredWeight;

        return $historicalStrength / ($winRateWeight + $goalsScoredWeight);

    }
}
