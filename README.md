# Football League Simulation
This project is a PHP-based application that simulates a football league, incorporating features like match fixtures, league standings, match simulations, and championship predictions. It's designed to mimic real-world football league dynamics and offers a predictive model for championship outcomes.

## Features
- **Team Management**: Handles team data, including team names and strengths.
- **Fixture Generation**: Automatically generates match fixtures for the league.
- **Match Simulation**: Simulates match outcomes based on team strengths, home advantage, and a randomness factor.
- **League Standings**: Tracks and updates league standings, including points, goals scored, and goals against.
- **Championship Prediction**: Predicts championship rates for teams based on current and potential points.

## Services
- `FixtureService`: Manages the creation and retrieval of match fixtures.
- `LeagueStandingService`: Manages league standings, updating them based on match outcomes.
- `MatchSimulationService`: Simulates matches and updates weekly outcomes.
- `PredictionService`: Calculates championship rates for teams based on current standings and potential outcomes.

## Setup
To set up the project:

1. Clone the repository.
2. Run `composer install` to install dependencies.
3. Configure your database settings in the `.env` file.
4. Run migrations using `php artisan migrate`.
5. Seed the database using `php artisan db:seed --class=TeamSeeder`
6. Run `npm install` to install frontend dependencies.
7. Build Vue files with `npm run build`

## Usage
- Run `php artisan serve`
- Generate fixtures for the league.
- Simulate matches either one week at a time or for the entire league schedule.
- View and track the league standings as matches are played.
- Get predictive championship rates for teams as the league progresses.
