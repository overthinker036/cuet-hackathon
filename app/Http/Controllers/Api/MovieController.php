<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movie;
use Illuminate\Http\JsonResponse;

class MovieController extends Controller
{
    public function index(): JsonResponse
    {
        $movies = Movie::query()
            ->with(['showtimes' => fn ($query) => $query->orderBy('starts_at')])
            ->orderBy('title')
            ->get();

        return response()->json($movies->map(fn (Movie $movie) => [
            'id' => $movie->id,
            'title' => $movie->title,
            'duration_minutes' => $movie->duration_minutes,
            'showtimes' => $movie->showtimes->map(fn ($showtime) => [
                'id' => $showtime->id,
                'theatre_id' => $showtime->theatre_id,
                'starts_at' => $showtime->starts_at->toISOString(),
            ])->values(),
        ])->values());
    }

    public function showtimes(Movie $movie): JsonResponse
    {
        $showtimes = $movie->showtimes()
            ->with('theatre')
            ->orderBy('starts_at')
            ->get();

        return response()->json($showtimes->map(fn ($showtime) => [
            'id' => $showtime->id,
            'movie_id' => $showtime->movie_id,
            'theatre_id' => $showtime->theatre_id,
            'theatre_name' => $showtime->theatre?->name,
            'starts_at' => $showtime->starts_at->toISOString(),
        ])->values());
    }
}
