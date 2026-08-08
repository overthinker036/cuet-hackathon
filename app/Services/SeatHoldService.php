<?php

namespace App\Services;

use App\Models\SeatHold;
use App\Models\ShowtimeSeat;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SeatHoldService
{
    public function reconcileSeatStatus(ShowtimeSeat $showtimeSeat): void
    {
        $activeHold = $showtimeSeat->holds()
            ->where('status', 'ACTIVE')
            ->orderByDesc('expires_at')
            ->first();

        if ($showtimeSeat->status === 'BOOKED') {
            return;
        }

        if ($activeHold && $activeHold->expires_at->isFuture()) {
            if ($showtimeSeat->status !== 'HELD') {
                $showtimeSeat->update(['status' => 'HELD']);
            }

            return;
        }

        $expiredHolds = $showtimeSeat->holds()
            ->where('status', 'ACTIVE')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredHolds as $expiredHold) {
            $expiredHold->update(['status' => 'EXPIRED']);
        }

        if ($showtimeSeat->status !== 'AVAILABLE') {
            $showtimeSeat->update(['status' => 'AVAILABLE']);
        }
    }

    public function holdForShowtimeSeat(int $showtimeId, int $seatId, string $holderRef): array
    {
        return DB::transaction(function () use ($showtimeId, $seatId, $holderRef) {
            $showtimeSeat = ShowtimeSeat::query()
                ->where('showtime_id', $showtimeId)
                ->where('seat_id', $seatId)
                ->lockForUpdate()
                ->first();

            if (! $showtimeSeat) {
                throw (new ModelNotFoundException())->setModel(ShowtimeSeat::class, [$showtimeId, $seatId]);
            }

            $this->reconcileSeatStatus($showtimeSeat);
            $showtimeSeat->refresh();

            if ($showtimeSeat->status === 'BOOKED') {
                throw new RuntimeException('Seat is already booked.', 409);
            }

            if ($showtimeSeat->status === 'HELD') {
                throw new RuntimeException('Seat is currently held.', 409);
            }

            $expiresAt = CarbonImmutable::now()->addSeconds((int) config('booking.hold_ttl_seconds', 300));

            $hold = SeatHold::query()->create([
                'hold_ref' => (string) Str::uuid(),
                'showtime_seat_id' => $showtimeSeat->id,
                'holder_ref' => $holderRef,
                'status' => 'ACTIVE',
                'expires_at' => $expiresAt,
            ]);

            $showtimeSeat->update([
                'status' => 'HELD',
            ]);

            return [
                'hold_ref' => $hold->hold_ref,
                'showtime' => $showtimeId,
                'seat' => $seatId,
                'status' => 'HELD',
                'expires_at' => $hold->expires_at->toIso8601String(),
            ];
        });
    }
}
