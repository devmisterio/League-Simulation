<?php

namespace App\Services;


use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Illuminate\Support\Collection;

class PredictionService
{
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
            $opponentStrength = $this->getTeamStrength($match->away_team_id);
            $potentialPoints += $this->estimateMatchOutcome($team->strength, $opponentStrength);
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

    private function estimateMatchOutcome($teamStrength, $opponentStrength): int
    {
        if ($teamStrength > $opponentStrength) {
            return 3;
        } elseif ($teamStrength == $opponentStrength) {
            return 1;
        } else {
            return 0;
        }
    }
}
