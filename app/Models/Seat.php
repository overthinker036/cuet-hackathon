<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'theatre_id',
        'row_label',
        'seat_number',
    ];

    public function theatre(): BelongsTo
    {
        return $this->belongsTo(Theatre::class);
    }

    public function showtimeSeats(): HasMany
    {
        return $this->hasMany(ShowtimeSeat::class);
    }
}
