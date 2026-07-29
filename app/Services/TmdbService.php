<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    public function nowPlaying(): array
    {
        return $this->client()
            ->get('/movie/now_playing', [
                'language' => config('services.tmdb.language'),
                'region' => config('services.tmdb.region'),
                'page' => 1,
            ])
            ->throw()
            ->json('results', []);
    }

    public function movie(int $tmdbId): array
    {
        return $this->client()
            ->get("/movie/{$tmdbId}", [
                'language' => config('services.tmdb.language'),
            ])
            ->throw()
            ->json();
    }

    public function posterUrl(?string $path, string $size = 'w500'): ?string
    {
        return $path ? "https://image.tmdb.org/t/p/{$size}{$path}" : null;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(self::BASE_URL)
            ->withToken((string) config('services.tmdb.token'))
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 200);
    }
}
