<?php

namespace App\Services;

use App\Models\Movie;

class MovieCollectionService
{
    public function findByTmdbId(int $tmdbId): ?Movie
    {
        return Movie::query()->where('tmdb_id', $tmdbId)->first();
    }

    public function firstOrCreateFromTmdb(array $tmdbMovie): Movie
    {
        return Movie::query()->firstOrCreate(
            ['tmdb_id' => $tmdbMovie['id']],
            [
                'title' => $tmdbMovie['title'],
                'overview' => $tmdbMovie['overview'] ?: null,
                'poster_path' => $tmdbMovie['poster_path'] ?? null,
                'release_date' => $tmdbMovie['release_date'] ?: null,
            ],
        );
    }
}
