<?php

namespace Tests\Feature;

use App\Models\Seat;
use App\Models\SeatHold;
use App\Models\Showtime;
use App\Models\ShowtimeSeat;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeatHoldApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_seat_can_be_held(): void
    {
        $this->seed();

        $response = $this->postJson('/api/showtimes/1/seats/1/hold', [
            'holder_ref' => 'user-a',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'HELD')
            ->assertJsonPath('showtime', 1)
            ->assertJsonPath('seat', 1);

        $this->assertDatabaseHas('showtime_seats', [
            'showtime_id' => 1,
            'seat_id' => 1,
            'status' => 'HELD',
        ]);
    }

    public function test_second_holder_is_rejected_for_active_hold(): void
    {
        $this->seed();

        $this->postJson('/api/showtimes/1/seats/1/hold', ['holder_ref' => 'user-a'])->assertStatus(201);

        $response = $this->postJson('/api/showtimes/1/seats/1/hold', ['holder_ref' => 'user-b']);

        $response->assertStatus(409);
    }

    public function test_booked_seat_is_rejected(): void
    {
        $this->seed();

        $showtimeSeat = ShowtimeSeat::query()->where('showtime_id', 1)->where('seat_id', 1)->firstOrFail();
        $showtimeSeat->update(['status' => 'BOOKED']);

        $response = $this->postJson('/api/showtimes/1/seats/1/hold', ['holder_ref' => 'user-a']);

        $response->assertStatus(409);
    }

    public function test_invalid_showtime_and_seat_combination_returns_not_found(): void
    {
        $this->seed();

        $response = $this->postJson('/api/showtimes/999/seats/999/hold', ['holder_ref' => 'user-a']);

        $response->assertStatus(404);
    }

    public function test_hold_expires_and_reuses_slot(): void
    {
        config()->set('booking.hold_ttl_seconds', 1);
        $this->seed();

        $this->postJson('/api/showtimes/1/seats/1/hold', ['holder_ref' => 'user-a'])->assertStatus(201);

        $hold = SeatHold::query()->first();
        $hold->update(['expires_at' => Carbon::now()->subSecond()]);

        $showtimeSeat = ShowtimeSeat::query()->where('showtime_id', 1)->where('seat_id', 1)->firstOrFail();
        $showtimeSeat->update(['status' => 'HELD']);

        $response = $this->postJson('/api/showtimes/1/seats/1/hold', ['holder_ref' => 'user-b']);

        $response->assertStatus(201);
        $this->assertDatabaseHas('seat_holds', ['holder_ref' => 'user-b']);
    }
}
