<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * Service class for managing league standings.
 */
class LeagueStandingService
{
    /**
     * Retrieve all teams.
     *
     * @return Collection A collection of all teams.
     */
    public function getAllTeams(): Collection
    {
        return Team::all();
    }

    /**
     * Creates initial league standings for each team if they don't already exist.
     */
    public function createInitialStandingsIfNotExists(): void
    {
        if (LeagueStandings::count() === 0) {
            Team::all()->each(function ($team) {
                LeagueStandings::create(['team_id' => $team->id]);
            });
        }
    }

    /**
     * Retrieves and formats the current league standings.
     *
     * @return Collection A collection of the formatted league standings.
     */
    public function getFormattedStandings(): Collection
    {
        return LeagueStandings::with("team")
            ->get()
            ->map(function ($standing) {
                $teamId = $standing->team->id;

                $matchesPlayed = FootballMatch::where(function ($query) use ($teamId) {
                    $query->where("home_team_id", $teamId)->whereNotNull("home_score");
                })
                    ->orWhere(function ($query) use ($teamId) {
                        $query->where("away_team_id", $teamId)->whereNotNull("away_score");
                    })
                    ->count();

                $wins = FootballMatch::where(function ($query) use ($teamId) {
                    $query
                        ->where("home_team_id", $teamId)
                        ->whereColumn("home_score", ">", "away_score");
                })
                    ->orWhere(function ($query) use ($teamId) {
                        $query
                            ->where("away_team_id", $teamId)
                            ->whereColumn("away_score", ">", "home_score");
                    })
                    ->count();

                $draws = FootballMatch::where(function ($query) use ($teamId) {
                    $query->where("home_team_id", $teamId)->orWhere("away_team_id", $teamId);
                })
                    ->whereColumn("home_score", "=", "away_score")
                    ->count();

                $losses = $matchesPlayed - $wins - $draws;
                $points = ($wins * 3) + $draws;

                return [
                    "id" => $standing->team->id,
                    "name" => $standing->team->name,
                    "played" => $matchesPlayed,
                    "won" => $wins,
                    "drawn" => $draws,
                    "lost" => $losses,
                    "points" => $points,
                    "goalDifference" => $standing->goals_difference
                ];
            })
            ->sortByDesc('points')
            ->values();
    }

    /**
     * Updates league standings based on a given match result.
     *
     * @param FootballMatch $match The match object to update standings with.
     */
    public function updateStandings(FootballMatch $match): void
    {
        $homeTeamStanding = LeagueStandings::firstOrCreate(['team_id' => $match->home_team_id]);
        $awayTeamStanding = LeagueStandings::firstOrCreate(['team_id' => $match->away_team_id]);

        $homeTeamStanding->goals_for += $match->home_score;
        $homeTeamStanding->goals_against += $match->away_score;
        $awayTeamStanding->goals_for += $match->away_score;
        $awayTeamStanding->goals_against += $match->home_score;

        if ($match->home_score > $match->away_score) {
            $homeTeamStanding->points += 3;
        } elseif ($match->home_score < $match->away_score) {
            $awayTeamStanding->points += 3;
        } else {
            $homeTeamStanding->points += 1;
            $awayTeamStanding->points += 1;
        }

        $homeTeamStanding->goals_difference = $homeTeamStanding->goals_for - $homeTeamStanding->goals_against;
        $awayTeamStanding->goals_difference = $awayTeamStanding->goals_for - $awayTeamStanding->goals_against;

        $homeTeamStanding->save();
        $awayTeamStanding->save();

        $this->recalculatePositions();
    }

    /**
     * Recalculates and updates the position of each team in the league standings.
     */
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
