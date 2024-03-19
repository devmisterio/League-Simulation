<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeagueStandings extends Model
{
    use HasFactory;

    protected $table = 'league_standings';

    protected $fillable = [
        'team_id',
        'points',
        'goals_for',
        'goals_against',
        'goal_difference',
        'position'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class, 'team_id');
    }
}
