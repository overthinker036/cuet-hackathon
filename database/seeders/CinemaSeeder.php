<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\Theatre;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $movieA = Movie::query()->create([
            'title' => 'Neon Horizon',
            'duration_minutes' => 132,
        ]);

        $movieB = Movie::query()->create([
            'title' => 'Velvet Circuit',
            'duration_minutes' => 118,
        ]);

        $theatre = Theatre::query()->create([
            'name' => 'Aurora Hall',
            'location' => 'Downtown',
        ]);

        $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $createdSeats = [];

        foreach ($rows as $rowLabel) {
            for ($seatNumber = 1; $seatNumber <= 5; $seatNumber++) {
                $createdSeats[] = Seat::query()->create([
                    'theatre_id' => $theatre->id,
                    'row_label' => $rowLabel,
                    'seat_number' => $seatNumber,
                ]);
            }
        }

        $showtimes = [
            ['movie_id' => $movieA->id, 'starts_at' => Carbon::now()->addDay()->setTime(12, 30)],
            ['movie_id' => $movieA->id, 'starts_at' => Carbon::now()->addDay()->setTime(17, 0)],
            ['movie_id' => $movieB->id, 'starts_at' => Carbon::now()->addDay()->setTime(20, 30)],
        ];

        foreach ($showtimes as $showtimeData) {
            $showtime = Showtime::query()->create([
                'movie_id' => $showtimeData['movie_id'],
                'theatre_id' => $theatre->id,
                'starts_at' => $showtimeData['starts_at'],
            ]);

            foreach ($createdSeats as $seat) {
                ShowtimeSeat::query()->create([
                    'showtime_id' => $showtime->id,
                    'seat_id' => $seat->id,
                    'price' => match (true) {
                        $seat->row_label === 'A' => 14.5,
                        $seat->row_label === 'B' => 13.5,
                        default => 11.5,
                    },
                    'status' => 'AVAILABLE',
                ]);
            }
        }
    }
}
