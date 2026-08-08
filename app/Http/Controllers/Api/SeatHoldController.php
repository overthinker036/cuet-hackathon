<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Seat;
use App\Models\Showtime;
use App\Services\SeatHoldService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SeatHoldController extends Controller
{
    public function __construct(
        protected SeatHoldService $seatHoldService,
    ) {}

    public function hold(Request $request, Showtime $showtime, Seat $seat): JsonResponse
    {
        $data = $request->validate([
            'holder_ref' => ['required', 'string', 'min:1'],
        ]);

        $showtimeSeat = $showtime->showtimeSeats()
            ->where('seat_id', $seat->id)
            ->first();

        if (! $showtimeSeat) {
            abort(404, 'Seat does not belong to the selected showtime.');
        }

        try {
            $result = $this->seatHoldService->holdForShowtimeSeat(
                $showtime->id,
                $seat->id,
                $data['holder_ref'],
            );

            return response()->json($result, 201);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->getCode() ?: 409);
        }
    }
}
