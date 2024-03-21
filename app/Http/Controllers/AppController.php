<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Models\LeagueStandings;
use App\Models\Team;
use App\Services\FixtureService;
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
}
