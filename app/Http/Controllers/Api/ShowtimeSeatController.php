<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use Illuminate\Http\JsonResponse;

class ShowtimeSeatController extends Controller
{
    public function index(Showtime $showtime): JsonResponse
    {
        $showtime->load(['showtimeSeats.seat', 'showtimeSeats']);

        $seats = $showtime->showtimeSeats
            ->sortBy(fn ($showtimeSeat) => [$showtimeSeat->seat->row_label, $showtimeSeat->seat->seat_number])
            ->values()
            ->map(fn ($showtimeSeat) => [
                'id' => $showtimeSeat->id,
                'seat_id' => $showtimeSeat->seat_id,
                'row' => $showtimeSeat->seat->row_label,
                'number' => $showtimeSeat->seat->seat_number,
                'price' => (float) $showtimeSeat->price,
                'status' => $showtimeSeat->status,
            ]);

        return response()->json([
            'showtime_id' => $showtime->id,
            'movie_id' => $showtime->movie_id,
            'theatre_id' => $showtime->theatre_id,
            'starts_at' => $showtime->starts_at->toISOString(),
            'seats' => $seats,
        ]);
    }
}
