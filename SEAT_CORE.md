# Seat Core

## Scope

This document covers the current Person 1 implementation for the CinemaSeat hackathon project.

The seat-core domain is responsible for:
- cinema catalogue data
- theatre, movie, showtime, and seat definitions
- seat map browsing
- atomic seat holds
- hold expiry and reuse
- seat-domain validation and automated tests

This scope intentionally excludes:
- booking and checkout logic
- OTP and payment flows
- gateway callbacks and webhook reliability
- CI/CD and deployment
- frontend polish

## Current state of the repository

The project is a Laravel application with a PostgreSQL-oriented schema and Eloquent models. The seat domain is implemented as a focused, test-backed subsystem inside the Laravel monolith.

The working branch for this feature is:
- `feat/core-schema`

The repository currently contains verified seat-domain functionality for:
- seed data generation
- API catalogue browsing
- showtime seat maps
- transactional seat holds
- expiration-aware seat reuse

## Data model

The following tables are implemented and seeded:

- `movies`
- `theatres`
- `seats`
- `showtimes`
- `showtime_seats`
- `seat_holds`

### Core model decisions

- `movies` store catalogue metadata such as title and runtime.
- `theatres` define the physical cinema location.
- `seats` model a physical seat inside a theatre.
- `showtimes` define scheduled screenings tied to a movie and theatre.
- `showtime_seats` is the per-showtime seat record and the main concurrency boundary.
- `seat_holds` records timelimited holds for a specific showtime seat.

### Concurrency intent

`showtime_seats.status` is treated as the authoritative in-row status for the seat-domain flow. PostgreSQL is the concurrency authority for seat correctness, and the hold logic uses row locking to prevent race-driven oversell.

## Schema and models

Implemented files include:
- [database/migrations/2026_08_08_000001_create_cinema_schema.php](database/migrations/2026_08_08_000001_create_cinema_schema.php)
- [app/Models/Movie.php](app/Models/Movie.php)
- [app/Models/Theatre.php](app/Models/Theatre.php)
- [app/Models/Seat.php](app/Models/Seat.php)
- [app/Models/Showtime.php](app/Models/Showtime.php)
- [app/Models/ShowtimeSeat.php](app/Models/ShowtimeSeat.php)
- [app/Models/SeatHold.php](app/Models/SeatHold.php)

Key constraints and indexes include:
- unique physical seat definition per theatre
- unique showtime-seat pairing
- foreign keys to movies, theatres, and seats
- indexes for showtime and hold lookup paths
- TTL-aware hold records with expiry timestamps

## Seed data

The app seeds a realistic sample cinema dataset:
- 2 movies
- 1 theatre
- 40 seats
- 3 showtimes
- 120 showtime-seat rows

Seed files:
- [database/seeders/CinemaSeeder.php](database/seeders/CinemaSeeder.php)
- [database/seeders/DatabaseSeeder.php](database/seeders/DatabaseSeeder.php)
- [database/factories/MovieFactory.php](database/factories/MovieFactory.php)
- [database/factories/TheatreFactory.php](database/factories/TheatreFactory.php)
- [database/factories/SeatFactory.php](database/factories/SeatFactory.php)
- [database/factories/ShowtimeFactory.php](database/factories/ShowtimeFactory.php)
- [database/factories/ShowtimeSeatFactory.php](database/factories/ShowtimeSeatFactory.php)

## APIs implemented

### Catalog endpoints

- `GET /api/movies`
- `GET /api/movies/{movie}/showtimes`

Implemented in:
- [app/Http/Controllers/Api/MovieController.php](app/Http/Controllers/Api/MovieController.php)

### Showtime seat map

- `GET /api/showtimes/{showtime}/seats`

Implemented in:
- [app/Http/Controllers/Api/ShowtimeSeatController.php](app/Http/Controllers/Api/ShowtimeSeatController.php)

### Hold endpoint

- `POST /api/showtimes/{showtime}/seats/{seat}/hold`

Implemented in:
- [app/Http/Controllers/Api/SeatHoldController.php](app/Http/Controllers/Api/SeatHoldController.php)

Request body:

```json
{
  "holder_ref": "user-a"
}
```

Response shape:

```json
{
  "hold_ref": "...uuid...",
  "showtime": 1,
  "seat": 1,
  "status": "HELD",
  "expires_at": "2026-08-08T12:34:56.000000Z"
}
```

## Hold logic and expiration model

The core seat-hold logic is centralized in:
- [app/Services/SeatHoldService.php](app/Services/SeatHoldService.php)

The implementation now does the following:
- starts a database transaction
- selects the target row with `lockForUpdate()`
- rejects booked seats
- rejects already held seats
- checks validity of existing active holds against TTL
- marks expired active holds as `EXPIRED`
- converts stale `HELD` rows back to `AVAILABLE` when needed
- creates a new active hold for the winner
- updates the seat row to `HELD`

The expiration policy is not dependent solely on a scheduler or worker. The application re-evaluates expired holds when:
- a new hold is attempted
- a seat map is requested

This means a seat becomes reusable immediately once its active hold has expired, which is required for Scenario B style behavior.

## TTL configuration

The hold lifetime is controlled through configuration:
- [config/booking.php](config/booking.php)

Configured via environment variable:

```env
HOLD_TTL_SECONDS=300
```

## Route registration

Current API routing is defined in:
- [routes/api.php](routes/api.php)

The application also exposes a health endpoint in:
- [routes/web.php](routes/web.php)

## Testing status

Feature tests cover the seat domain here:
- [tests/Feature/SeatDomainSchemaTest.php](tests/Feature/SeatDomainSchemaTest.php)
- [tests/Feature/SeatMapApiTest.php](tests/Feature/SeatMapApiTest.php)
- [tests/Feature/SeatHoldApiTest.php](tests/Feature/SeatHoldApiTest.php)

Covered scenarios include:
- schema and seeding validity
- movie catalogue route behavior
- seat map availability and structure
- available seat can be held
- second holder is rejected
- booked seat is rejected
- invalid showtime/seat combination returns 404
- expired hold becomes reusable
- stale `HELD` seat state is normalized to `AVAILABLE`

## Verification evidence

The latest verification command run was:

```bash
php artisan test --filter='SeatHoldApiTest|SeatMapApiTest|SeatDomainSchemaTest'
```

Result:
- 9 tests passed
- 296 assertions
- successful exit

## Important design notes

This implementation is intentionally focused on domain correctness for the seat and hold flow. It does not attempt to include:
- gateway asynchronous payment logic
- OTP validation flows
- webhooks handling
- booking persistence beyond the seat domain itself
- frontend or deployment concerns

That separation keeps the work aligned to the required responsibilities and reduces cross-team code collision during the hackathon.

## Summary

The seat-core feature is now in a coherent state:
- catalogue exists and is seeded
- seat-map API works
- atomic hold flow works
- expiration and stale-seat recovery are handled
- automated tests validate the current behavior

This is the current working baseline for the Person 1 domain before the next concurrency hardening and proof-oriented validation phase.
