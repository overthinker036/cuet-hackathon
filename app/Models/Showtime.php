<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Showtime extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'theatre_id',
        'starts_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
    ];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }
}
