<?php

use App\Http\Controllers\Api\MovieController;
use App\Http\Controllers\Api\SeatHoldController;
use App\Http\Controllers\Api\ShowtimeSeatController;
use Illuminate\Support\Facades\Route;

Route::get('/movies', [MovieController::class, 'index']);
Route::get('/movies/{movie}/showtimes', [MovieController::class, 'showtimes']);
Route::get('/showtimes/{showtime}/seats', [ShowtimeSeatController::class, 'index']);
Route::post('/showtimes/{showtime}/seats/{seat}/hold', [SeatHoldController::class, 'hold']);
