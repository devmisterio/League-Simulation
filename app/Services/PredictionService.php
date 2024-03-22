<?php

namespace App\Services;


use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Illuminate\Support\Collection;

class PredictionService
{
    private const HOME_ADVANTAGE_WEIGHT = 1.1;
    private const RANDOMNESS_FACTOR = 10;

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

    private function predictLeagueStandings(): Collection
    {
        $standings = LeagueStandings::with('team')->get();

        foreach ($standings as $standing) {
            $potentialPoints = $this->calculatePotentialPoints($standing->team);
            $standing->predicted_points = $standing->points + $potentialPoints;
        }

        return $standings->sortByDesc('predicted_points');
    }

    private function calculatePotentialPoints($team): int
    {
        $remainingMatches = $this->getRemainingMatchesForTeam($team->id);
        $potentialPoints = 0;

        foreach ($remainingMatches as $match) {
            $isHome = $match->home_team_id === $team->id;
            $opponentStrength = $this->getTeamStrength($isHome ? $match->away_team_id : $match->home_team_id);
            $potentialPoints += $this->estimateMatchOutcome($team->strength, $opponentStrength, $isHome);
        }

        return $potentialPoints;
    }

    private function getRemainingMatchesForTeam($teamId): Collection
    {
        return FootballMatch::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                ->orWhere('away_team_id', $teamId);
        })->whereNull('home_score')->get();
    }

    private function getTeamStrength($teamId): int
    {
        return Team::find($teamId)->strength;
    }

    private function estimateMatchOutcome($teamStrength, $opponentStrength, $isHome): int
    {
        $adjustedTeamStrength = $isHome ? $teamStrength * self::HOME_ADVANTAGE_WEIGHT : $teamStrength;
        $strengthDifference = $adjustedTeamStrength - $opponentStrength;

        $strengthDifference += rand(-self::RANDOMNESS_FACTOR, self::RANDOMNESS_FACTOR);

        if ($strengthDifference > 20) {
            return 3; // Win
        } elseif ($strengthDifference > -20) {
            return 1; // Draw
        } else {
            return 0; // Loss
        }
    }
}
