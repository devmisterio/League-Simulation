<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;

class LeagueStandingService
{
    public function updateStandings(FootballMatch $match)
    {
        $homeTeamStanding = LeagueStandings::firstOrCreate(['team_id' => $match->home_team_id]);
        $awayTeamStanding = LeagueStandings::firstOrCreate(['team_id' => $match->away_team_id]);

        // Update goals for and against
        $homeTeamStanding->goals_for += $match->home_score;
        $homeTeamStanding->goals_against += $match->away_score;
        $awayTeamStanding->goals_for += $match->away_score;
        $awayTeamStanding->goals_against += $match->home_score;

        // Determine the outcome of the match and update points and goal difference
        if ($match->home_score > $match->away_score) { // Home team wins
            $homeTeamStanding->points += 3;
        } elseif ($match->home_score < $match->away_score) { // Away team wins
            $awayTeamStanding->points += 3;
        } else { // Draw
            $homeTeamStanding->points += 1;
            $awayTeamStanding->points += 1;
        }

        // Update goal difference
        $homeTeamStanding->goals_difference = $homeTeamStanding->goals_for - $homeTeamStanding->goals_against;
        $awayTeamStanding->goals_difference = $awayTeamStanding->goals_for - $awayTeamStanding->goals_against;

        $homeTeamStanding->save();
        $awayTeamStanding->save();

        // Recalculate positions
        $this->recalculatePositions();
    }

    private function recalculatePositions()
    {
        $standings = LeagueStandings::orderBy('points', 'desc')
            ->orderBy('goals_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        foreach ($standings as $index => $standing) {
            $standing->position = $index + 1;
            $standing->save();
        }
    }
}
