<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use App\Services\FixtureService;
use App\Services\LeagueStandingService;
use App\Services\MatchSimulationService;
use Carbon\Carbon;
use Inertia\Inertia;

class AppController extends Controller
{
    public function index()
    {
        return Inertia::render('Index', [
            'teams' => Team::all()
        ]);
    }

    public function fixtures()
    {
        FootballMatch::truncate();
        LeagueStandings::truncate();

        $fixtureService = new FixtureService();
        $fixtureService->generateFixtures();

        $weeklyFixtures = FootballMatch::with(["homeTeam", "awayTeam"])
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


        return Inertia::render('Fixtures', [
            'weeklyFixtures' => $weeklyFixtures
        ]);
    }

    public function simulation(LeagueStandingService $leagueStandingService)
    {
        // Check if standings data is empty and create initial data if it is
        if (LeagueStandings::count() === 0) {
            $leagueStandingService->createInitialStandingsData();
        }

        // Fetch the standings data
        $league = LeagueStandings::with("team")
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

        $firstMatch = FootballMatch::orderBy("id", "asc")->first("match_date");
        $leagueStartDate = Carbon::parse($firstMatch->match_date);

        $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
            ->whereNull("home_score")
            ->orderBy("id", "asc") // Ensure they are ordered by date
            ->take(2)
            ->get();

        if ($currentWeekMatches->isEmpty()) {
            // If no matches with null scores, get the last 2 matches from the table
            $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
                ->orderBy("id", "desc") // Order by descending to get the last matches
                ->take(2)
                ->get()
                ->reverse(); // Reverse to maintain chronological order
        }

        $currentWeekMatches->transform(function ($match) use ($leagueStartDate) {
            // Calculate the week number based on the match date and league start date
            $match->week_number =
                (int) Carbon::parse($leagueStartDate)->diffInWeeks($match->match_date) + 1;
            return $match;
        });



        return Inertia::render('Simulation', [
            'league' => $league,
            'currentWeekMatches' => $currentWeekMatches
        ]);
    }

    public function playWeek()
    {
        $matches = FootballMatch::whereNull("home_score")->take(2)->get();

        $matchSimulationService = new MatchSimulationService();
        $leagueStandingService = new LeagueStandingService();

        foreach ($matches as $match) {
            $matchSimulationService->simulateMatch($match);
            $leagueStandingService->updateStandings($match);
        }

        // Fetch the standings data
        $league = LeagueStandings::with("team")
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
            ->sortByDesc('points');;

        $firstMatch = FootballMatch::orderBy("id", "asc")->first("match_date");
        $leagueStartDate = Carbon::parse($firstMatch->match_date);

        $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
            ->whereNull("home_score")
            ->orderBy("id", "asc") // Ensure they are ordered by date
            ->take(2)
            ->get();

        if ($currentWeekMatches->isEmpty()) {
            // If no matches with null scores, get the last 2 matches from the table
            $currentWeekMatches = FootballMatch::with(["homeTeam", "awayTeam"])
                ->orderBy("id", "desc") // Order by descending to get the last matches
                ->take(2)
                ->get()
                ->reverse(); // Reverse to maintain chronological order
        }

        $currentWeekMatches->transform(function ($match) use ($leagueStartDate) {
            // Calculate the week number based on the match date and league start date
            $match->week_number =
                (int) Carbon::parse($leagueStartDate)->diffInWeeks($match->match_date) + 1;
            return $match;
        });



        return response()->json([
            'league' => $league,
            'currentWeekMatches' => $currentWeekMatches
        ]);
    }
}
