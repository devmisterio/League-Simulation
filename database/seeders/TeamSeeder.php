<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teams = [
            ['name' => 'Liverpool', 'strength' => 90],
            ['name' => 'Manchester City', 'strength' => 92],
            ['name' => 'Chelsea', 'strength' => 88],
            ['name' => 'Arsenal', 'strength' => 86],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }
    }
}
