<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShowtimeSeat>
 */
class ShowtimeSeatFactory extends Factory
{
    protected $model = ShowtimeSeat::class;

    public function definition(): array
    {
        return [
            'showtime_id' => Showtime::factory(),
            'seat_id' => Seat::factory(),
            'price' => $this->faker->randomFloat(2, 8.5, 25.0),
            'status' => 'AVAILABLE',
        ];
    }
}
