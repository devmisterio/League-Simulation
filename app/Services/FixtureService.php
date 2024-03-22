<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service class responsible for managing football match fixtures.
 */
class FixtureService
{
    /**
     * Collection of teams.
     *
     * @var Collection
     */
    private Collection $teams;

    /**
     * FixtureService constructor.
     */
    public function __construct()
    {
        $this->teams = Team::all();
    }

    /**
     * Generates new fixtures and retrieves them in a formatted array.
     *
     * @return array An array of formatted fixtures.
     */
    public function generateAndRetrieveFixtures(): array
    {
        $this->resetExistingData();
        $this->generateFixtures();

        return $this->formatFixturesForResponse();
    }

    /**
     * Resets existing match and league standings data.
     */
    private function resetExistingData(): void
    {
        FootballMatch::truncate();
        LeagueStandings::truncate();
    }

    /**
     * Generates fixtures based on a predefined pattern.
     */
    private function generateFixtures(): void
    {
        $fixturePattern = $this->getFixturePattern();
        $matchDate = Carbon::now();

        foreach ($fixturePattern as $weekMatches) {
            foreach (array_chunk($weekMatches, 2) as $matchPair) {
                $this->createMatch($matchPair, $matchDate);
            }
            $matchDate->addWeek(); // Increment the date by one week
        }
    }

    /**
     * Defines a pattern for football match fixtures.
     *
     * @return array An array representing the fixture pattern.
     */
    private function getFixturePattern(): array
    {
        return [
            [0, 1, 2, 3], // Week 1 matchups: Team 0 vs 1, and Team 2 vs 3
            [0, 2, 1, 3], // Week 2 matchups: Team 0 vs 2, and Team 1 vs 3
            [0, 3, 1, 2], // Week 3 matchups: Team 0 vs 3, and Team 1 vs 2
            [1, 0, 3, 2], // Week 4 matchups: Team 1 vs 0, and Team 2 vs 3
            [2, 0, 3, 1], // Week 5 matchups: Team 2 vs 0, and Team 3 vs 1
            [3, 0, 2, 1], // Week 6 matchups: Team 3 vs 0, and Team 2 vs 1
        ];
    }

    /**
     * Creates a football match with given team pair and match date.
     *
     * @param array $matchPair Array containing IDs of the home and away teams.
     * @param Carbon $matchDate The date of the match.
     */
    private function createMatch(array $matchPair, Carbon $matchDate): void
    {
        FootballMatch::create([
            'home_team_id' => $this->teams[$matchPair[0]]->id,
            'away_team_id' => $this->teams[$matchPair[1]]->id,
            'match_date' => $matchDate
        ]);
    }

    /**
     * Formats and groups fixtures for response.
     *
     * @return array An array of grouped and formatted fixtures.
     */
    private function formatFixturesForResponse(): array
    {
        return FootballMatch::with(["homeTeam", "awayTeam"])
            ->get()
            ->groupBy(fn($match) => Carbon::parse($match->match_date)->format("W"))
            ->map(fn($weekMatches) => $this->formatWeekMatches($weekMatches))
            ->toArray();
    }

    /**
     * Formats a week's matches for response.
     *
     * @param Collection $weekMatches Matches of a specific week.
     * @return Collection A collection of formatted week matches.
     */
    private function formatWeekMatches($weekMatches): Collection
    {
        return $weekMatches->map(function ($match) {
            return [
                "home_team" => $match->homeTeam->name,
                "away_team" => $match->awayTeam->name
            ];
        });
    }
}
