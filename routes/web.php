<?php

use App\Http\Controllers\AppController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppController::class, 'index']);
Route::get('/fixtures', [AppController::class, 'fixtures']);
Route::get('/simulation', [AppController::class, 'simulation']);
Route::get('/play-week', [AppController::class, 'playWeek']);
