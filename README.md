# CinemaSeat

This repository is the current Person 1 seat-core implementation for the CinemaSeat hackathon challenge.

## Current responsibility scope

Person 1 owns the seat-domain flow only:
- cinema catalogue data
- theatres, movies, showtimes, and seats
- seat map APIs
- atomic seat holds
- hold expiration and reuse
- seat-domain tests

This does not include booking, payment, OTP, gateway webhooks, CI/CD, deployment, or frontend work.

## Current state

The project is a Laravel modular monolith using PostgreSQL-compatible schema conventions and Eloquent models. The seat-domain layer is implemented and validated with Laravel feature tests.

## Database model

The following tables are present and seeded:
- movies
- theatres
- seats
- showtimes
- showtime_seats
- seat_holds

Core design decisions:
- `showtime_seats` stores the per-showtime seat state
- `seat_holds` records hold history and expiry
- `showtime_seats.status` is the authoritative row-level seat state for the seat-domain flow
- hold expiry is reconciled before reads and before new holds are accepted

## Seeded sample data

The app seeds:
- 2 movies
- 1 theatre
- 40 seats
- 3 showtimes
- 120 showtime-seat rows

This gives a realistic default cinema layout for the seat-map and hold flow without requiring an admin portal.

## API contract

### Seat map

```bash
GET /api/movies
GET /api/movies/{movie}/showtimes
GET /api/showtimes/{showtime}/seats
```

### Hold a seat

```bash
POST /api/showtimes/{showtime}/seats/{seat}/hold
Content-Type: application/json

{
  "holder_ref": "user-a"
}
```

Successful response example:

```json
{
  "hold_ref": "...uuid...",
  "showtime": 1,
  "seat": 1,
  "status": "HELD",
  "expires_at": "2026-08-08T12:34:56.000000Z"
}
```

Expected behavior:
- available seat: 201
- duplicate hold on same seat: 409
- booked seat: 409
- mismatched showtime/seat: 404
- expired hold becomes reusable without scheduler dependence

## Hold expiration rule

The system treats a seat as logically available when:
- `showtime_seats.status == HELD`
- and the active hold is expired

This is reconciled during:
- new hold attempts
- seat map reads

This avoids depending entirely on scheduler cleanup and keeps the domain correct even when stale records exist.

## Configuration

TTL is configured through:

```env
HOLD_TTL_SECONDS=300
```

## Verification status

The current seat-domain feature tests pass with:

```bash
php artisan test --filter='SeatHoldApiTest|SeatMapApiTest|SeatDomainSchemaTest'
```

Evidence from the latest run:
- 9 tests passed
- 296 assertions
- successful exit

## File highlights

Key files in the current seat-domain implementation:
- [database/migrations/2026_08_08_000001_create_cinema_schema.php](database/migrations/2026_08_08_000001_create_cinema_schema.php)
- [app/Services/SeatHoldService.php](app/Services/SeatHoldService.php)
- [app/Http/Controllers/Api/ShowtimeSeatController.php](app/Http/Controllers/Api/ShowtimeSeatController.php)
- [app/Http/Controllers/Api/SeatHoldController.php](app/Http/Controllers/Api/SeatHoldController.php)
- [routes/api.php](routes/api.php)
- [tests/Feature/SeatHoldApiTest.php](tests/Feature/SeatHoldApiTest.php)

## Next responsibility

The next focus is the stronger concurrency review and validation against the required 100-request same-seat contention model, with the understanding that Person 2 owns payment, OTP, gateway, and checkout logic and Person 3 owns deployment and proof tooling.
