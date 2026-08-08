<?php

namespace Database\Factories;

use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Theatre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Showtime>
 */
class ShowtimeFactory extends Factory
{
    protected $model = Showtime::class;

    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'theatre_id' => Theatre::factory(),
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+7 days'),
        ];
    }
}
