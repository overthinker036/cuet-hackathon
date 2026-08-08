<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatMapApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_movies_endpoint_returns_seeded_catalogue(): void
    {
        $this->seed();

        $response = $this->getJson('/api/movies');

        $response->assertOk();
        $response->assertJsonCount(2, '*');
        $response->assertJsonStructure([
            '*' => [
                'id',
                'title',
                'duration_minutes',
                'showtimes' => [
                    '*' => [
                        'id',
                        'theatre_id',
                        'starts_at',
                    ],
                ],
            ],
        ]);
    }

    public function test_showtime_seat_map_returns_seat_rows_and_statuses(): void
    {
        $this->seed();

        $response = $this->getJson('/api/showtimes/1/seats');

        $response->assertOk();
        $response->assertJsonStructure([
            'showtime_id',
            'movie_id',
            'theatre_id',
            'starts_at',
            'seats' => [
                '*' => [
                    'id',
                    'seat_id',
                    'row',
                    'number',
                    'price',
                    'status',
                ],
            ],
        ]);
        $this->assertNotEmpty($response->json('seats'));
    }
}
