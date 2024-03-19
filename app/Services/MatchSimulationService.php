<?php

namespace App\Services;

use App\Models\FootballMatch;

class MatchSimulationService
{
    public function simulateMatch(FootballMatch $match)
    {
        $homeTeam = $match->homeTeam;
        $awayTeam = $match->awayTeam;

        // Home team advantage multiplier
        $homeAdvantage = 1.1;
        $randomFactor = rand(-50, 50); // Random factor range adjusted

        // Calculating the strength difference
        $strengthDifference = ($homeTeam->strength * $homeAdvantage) - $awayTeam->strength + $randomFactor;

        // Determining match outcome based on strength difference
        if ($strengthDifference > 20) { // Adjusted threshold for home win
            // Home team wins
            $match->home_score = rand(1, 3);
            $match->away_score = rand(0, $match->home_score - 1);
        } elseif ($strengthDifference < -20) { // Adjusted threshold for away win
            // Away team wins
            $match->away_score = rand(1, 3);
            $match->home_score = rand(0, $match->away_score - 1);
        } else {
            // Draw
            $score = rand(0, 2);
            $match->home_score = $score;
            $match->away_score = $score;
        }

        $match->save();

    }
}
