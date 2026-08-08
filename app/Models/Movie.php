<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'duration_minutes',
    ];

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtime::class);
    }
}
