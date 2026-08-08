<?php

namespace Tests\Feature;

use App\Models\Movie;
use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use App\Models\Theatre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatDomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cinema_seed_data_is_available(): void
    {
        $this->seed();

        $this->assertDatabaseCount('movies', 2);
        $this->assertDatabaseCount('theatres', 1);
        $this->assertDatabaseCount('seats', 40);
        $this->assertDatabaseCount('showtimes', 3);
        $this->assertDatabaseCount('showtime_seats', 120);
        $this->assertDatabaseCount('seat_holds', 0);

        $this->assertTrue(Movie::query()->exists());
        $this->assertTrue(Theatre::query()->exists());
        $this->assertTrue(Seat::query()->exists());
        $this->assertTrue(Showtime::query()->exists());
        $this->assertTrue(ShowtimeSeat::query()->exists());
        $this->assertSame(0, SeatHold::query()->count());
    }
}
