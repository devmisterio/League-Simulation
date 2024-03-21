<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Carbon\Carbon;

class FixtureService
{
    public function generateAndRetrieveFixtures(): array
    {
        FootballMatch::truncate();
        LeagueStandings::truncate();

        $this->generateFixtures();

        return FootballMatch::with(["homeTeam", "awayTeam"])
            ->get()
            ->groupBy(function ($match) {
                return Carbon::parse($match->match_date)->format("W"); // Group by week number
            })
            ->map(function ($weekMatches) {
                return $weekMatches->map(function ($match) {
                    return [
                        "home_team" => $match->homeTeam->name,
                        "away_team" => $match->awayTeam->name
                    ];
                });
            })
            ->toArray();
    }
    private function generateFixtures()
    {
        $teams = Team::all();

        $matchDate = Carbon::now();

        $fixturePattern = [
            [0, 1, 2, 3], // Week 1 matchups: Team 0 vs 1, and Team 2 vs 3
            [0, 2, 1, 3], // Week 2 matchups: Team 0 vs 2, and Team 1 vs 3
            [0, 3, 1, 2], // Week 3 matchups: Team 0 vs 3, and Team 1 vs 2
            [1, 0, 3, 2], // Week 4 matchups: Team 1 vs 0, and Team 2 vs 3
            [2, 0, 3, 1], // Week 5 matchups: Team 2 vs 0, and Team 3 vs 1
            [3, 0, 2, 1], // Week 6 matchups: Team 3 vs 0, and Team 2 vs 1
        ];

        foreach (range(1, 6) as $week) {
            $weekPattern = $fixturePattern[$week % 6];

            FootballMatch::create([
                'home_team_id' => $teams[$weekPattern[0]]->id,
                'away_team_id' => $teams[$weekPattern[1]]->id,
                'match_date' => $matchDate
            ]);

            FootballMatch::create([
                'home_team_id' => $teams[$weekPattern[2]]->id,
                'away_team_id' => $teams[$weekPattern[3]]->id,
                'match_date' => $matchDate
            ]);

            $matchDate = $matchDate->addWeek(); // Increment the date by one week
        }
    }
}
