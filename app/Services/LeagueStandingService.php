<?php

namespace App\Services;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use Illuminate\Support\Collection;

class LeagueStandingService
{
    public function getAllTeams(): Collection
    {
        return Team::all();
    }

    public function createInitialStandingsIfNotExists(): void
    {
        if (LeagueStandings::count() === 0) {
            Team::all()->each(function ($team) {
                LeagueStandings::create(['team_id' => $team->id]);
            });
        }
    }

    public function getFormattedStandings(): Collection
    {
        return LeagueStandings::with("team")
            ->get()
            ->map(function ($standing) {
                $teamId = $standing->team->id;

                // Maçların oynanma durumunu hesapla
                $matchesPlayed = FootballMatch::where(function ($query) use ($teamId) {
                    $query->where("home_team_id", $teamId)->whereNotNull("home_score");
                })
                    ->orWhere(function ($query) use ($teamId) {
                        $query->where("away_team_id", $teamId)->whereNotNull("away_score");
                    })
                    ->count();

                // Kazanılan maç sayısını hesapla
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

                // Berabere biten maç sayısını hesapla
                $draws = FootballMatch::where(function ($query) use ($teamId) {
                    $query->where("home_team_id", $teamId)->orWhere("away_team_id", $teamId);
                })
                    ->whereColumn("home_score", "=", "away_score")
                    ->count();

                // Kaybedilen maç sayısını hesapla
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
