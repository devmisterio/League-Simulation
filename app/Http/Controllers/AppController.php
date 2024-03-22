<?php

namespace App\Http\Controllers;

use App\Models\FootballMatch;
use App\Services\FixtureService;
use App\Services\LeagueStandingService;
use App\Services\MatchSimulationService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppController extends Controller
{
    public function index(LeagueStandingService $service): Response
    {
        $teams = $service->getAllTeams();

        return Inertia::render('Index', compact('teams'));
    }

    public function fixtures(FixtureService $service): Response
    {
        $weeklyFixtures = $service->generateAndRetrieveFixtures();

        return Inertia::render('Fixtures', compact('weeklyFixtures'));
    }

    public function simulation(LeagueStandingService $leagueStandingService, MatchSimulationService $matchSimulationService): Response
    {
        $leagueStandingService->createInitialStandingsIfNotExists();

        $league = $leagueStandingService->getFormattedStandings();
        $currentWeekMatches = $matchSimulationService->getCurrentWeekMatches();


        return Inertia::render('Simulation', [
            'league' => $league,
            'currentWeekMatches' => $currentWeekMatches
        ]);
    }

    public function playWeek(MatchSimulationService $matchSimulationService, LeagueStandingService $leagueStandingService): JsonResponse
    {
        $matchSimulationService->playAndUpdateWeek();

        $league = $leagueStandingService->getFormattedStandings();
        $currentWeekMatches = $matchSimulationService->getCurrentWeekMatches();

        return response()->json(compact('league', 'currentWeekMatches'));
    }

    public function playAllWeek(MatchSimulationService $matchSimulationService, LeagueStandingService $leagueStandingService): JsonResponse
    {
        $matches = FootballMatch::whereNull("home_score")
            ->get()
            ->all();

        foreach ($matches as $match) {
            $matchSimulationService->simulateMatch($match);
            $leagueStandingService->updateStandings($match);
        }

        $league = $leagueStandingService->getFormattedStandings();
        $currentWeekMatches = $matchSimulationService->getCurrentWeekMatches();

        return response()->json(compact('league', 'currentWeekMatches'));
    }
}
