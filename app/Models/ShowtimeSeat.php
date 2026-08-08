<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShowtimeSeat extends Model
{
    use HasFactory;

    protected $table = 'showtime_seats';

    protected $fillable = [
        'showtime_id',
        'seat_id',
        'price',
        'status',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function showtime(): BelongsTo
    {
        return $this->belongsTo(Showtime::class);
    }

    public function seat(): BelongsTo
    {
        return $this->belongsTo(Seat::class);
    }

    public function holds(): HasMany
    {
        return $this->hasMany(SeatHold::class);
    }
}
