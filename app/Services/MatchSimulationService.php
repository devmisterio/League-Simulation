<?php

namespace App\Services;

use App\Models\FootballMatch;
use Carbon\Carbon;

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

    public function playAndUpdateWeek(): void
    {
        $matches = FootballMatch::whereNull("home_score")->take(2)->get();

        $leagueService = new LeagueStandingService();
        foreach ($matches as $match) {
            // Simulate the match
            $this->simulateMatch($match);

            // Update league standings
            $leagueService->updateStandings($match);
        }
    }

    public function getCurrentWeekMatches()
    {
        $firstMatch = FootballMatch::orderBy("id", "asc")->first("match_date");
        $leagueStartDate = Carbon::parse($firstMatch->match_date);

        $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
            ->whereNull("home_score")
            ->orderBy("id", "asc") // Ensure they are ordered by date
            ->take(2)
            ->get();

        if ($currentWeekMatches->isEmpty()) {
            $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
                ->orderBy("id", "desc") // Order by descending to get the last matches
                ->take(2)
                ->get()
                ->reverse(); // Reverse to maintain chronological order
        }

        $currentWeekMatches->transform(function ($match) use ($leagueStartDate) {
            $match->week_number =
                (int) Carbon::parse($leagueStartDate)->diffInWeeks($match->match_date) + 1;
            return $match;
        });

        return $currentWeekMatches;
    }
}
