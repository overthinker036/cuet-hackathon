<?php

namespace Database\Factories;

use App\Models\Theatre;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Theatre>
 */
class TheatreFactory extends Factory
{
    protected $model = Theatre::class;

    public function definition(): array
    {
        return [
            'name' => 'Cinema ' . $this->faker->randomElement(['Blue', 'Harbor', 'Aurora', 'Main']),
            'location' => $this->faker->city(),
        ];
    }
}
