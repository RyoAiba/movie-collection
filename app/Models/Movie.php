<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = [
        'tmdb_id',
        'title',
        'overview',
        'poster_path',
        'release_date',
        'rating',
        'review',
    ];

    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'rating' => 'integer',
        ];
    }
}
