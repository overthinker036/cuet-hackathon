<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('duration_minutes');
            $table->timestamps();
        });

        Schema::create('theatres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location');
            $table->timestamps();
        });

        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('theatre_id')->constrained()->cascadeOnDelete();
            $table->string('row_label', 8);
            $table->unsignedInteger('seat_number');
            $table->timestamps();

            $table->unique(['theatre_id', 'row_label', 'seat_number']);
            $table->index('theatre_id');
        });

        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->foreignId('theatre_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestamps();

            $table->index(['movie_id', 'starts_at']);
            $table->index('theatre_id');
        });

        Schema::create('showtime_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained()->cascadeOnDelete();
            $table->foreignId('seat_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 8, 2);
            $table->string('status')->default('AVAILABLE');
            $table->timestamps();

            $table->unique(['showtime_id', 'seat_id']);
            $table->index('showtime_id');
            $table->index('seat_id');
            $table->index('status');
        });

        Schema::create('seat_holds', function (Blueprint $table) {
            $table->id();
            $table->uuid('hold_ref')->unique();
            $table->foreignId('showtime_seat_id')->constrained()->cascadeOnDelete();
            $table->string('holder_ref');
            $table->string('status')->default('ACTIVE');
            $table->timestampTz('expires_at');
            $table->timestamps();

            $table->index('showtime_seat_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seat_holds');
        Schema::dropIfExists('showtime_seats');
        Schema::dropIfExists('showtimes');
        Schema::dropIfExists('seats');
        Schema::dropIfExists('theatres');
        Schema::dropIfExists('movies');
    }
};
