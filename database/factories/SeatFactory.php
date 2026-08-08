<?php

namespace Database\Factories;

use App\Models\Seat;
use App\Models\Theatre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Seat>
 */
class SeatFactory extends Factory
{
    protected $model = Seat::class;

    public function definition(): array
    {
        return [
            'theatre_id' => Theatre::factory(),
            'row_label' => $this->faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']),
            'seat_number' => $this->faker->numberBetween(1, 10),
        ];
    }
}
