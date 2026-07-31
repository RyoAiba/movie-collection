<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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

    public function popularMovies(CarbonInterface $today): array
    {
        $period = $this->fiscalYearPeriod($today);
        $movies = $this->client()
            ->get('/discover/movie', [
                'language' => config('services.tmdb.language'),
                'region' => config('services.tmdb.region'),
                'primary_release_date.gte' => $period['start']->toDateString(),
                'primary_release_date.lte' => $period['end']->toDateString(),
                'sort_by' => 'popularity.desc',
                'page' => 1,
            ])
            ->throw()
            ->json('results', []);

        return array_slice(
            array_values(array_filter($movies, fn (array $movie): bool => ! empty($movie['poster_path']))),
            0,
            20,
        );
    }

    /**
     * @return array{year: int, start: CarbonImmutable, end: CarbonImmutable}
     */
    public function fiscalYearPeriod(CarbonInterface $today): array
    {
        $date = CarbonImmutable::instance($today)->startOfDay();
        $year = $date->month >= 4 ? $date->year : $date->year - 1;
        $start = CarbonImmutable::create($year, 4, 1, 0, 0, 0, $date->timezone);
        $fiscalYearEnd = CarbonImmutable::create($year + 1, 3, 31, 0, 0, 0, $date->timezone);

        return [
            'year' => $year,
            'start' => $start,
            'end' => $date->min($fiscalYearEnd),
        ];
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

    public function movieDetails(int $tmdbId): array
    {
        return $this->client()
            ->get("/movie/{$tmdbId}", [
                'language' => config('services.tmdb.language'),
                'append_to_response' => 'credits,videos',
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
