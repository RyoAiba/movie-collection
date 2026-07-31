<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    private const VIDEO_CACHE_SECONDS = 21600;

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

    public function movieReviews(int $tmdbId): array
    {
        return $this->client()
            ->get("/movie/{$tmdbId}/reviews", [
                'language' => config('services.tmdb.language'),
                'page' => 1,
            ])
            ->throw()
            ->json('results', []);
    }

    public function trailerCarouselCandidates(array $movies): array
    {
        $candidates = [];
        $seenMovieIds = [];

        foreach (array_slice($movies, 0, 10) as $movie) {
            $movieId = (int) ($movie['id'] ?? 0);

            if (! $movieId || isset($seenMovieIds[$movieId])) {
                continue;
            }

            $seenMovieIds[$movieId] = true;
            $videos = [];

            foreach ([config('services.tmdb.language'), 'en-US'] as $language) {
                try {
                    $videos = [...$videos, ...$this->movieVideos($movieId, $language)];
                } catch (ConnectionException|RequestException) {
                    // A failed video request must not prevent other movies from being considered.
                }
            }

            $video = collect($videos)
                ->filter(fn (array $video): bool => ($video['site'] ?? null) === 'YouTube'
                    && in_array($video['type'] ?? null, ['Trailer', 'Teaser'], true)
                    && ! empty($video['key']))
                ->unique('key')
                ->sortBy(fn (array $video): array => [
                    ($video['type'] ?? null) === 'Trailer' ? 0 : 1,
                    match ($video['iso_639_1'] ?? null) {
                        'ja' => 0,
                        'en' => 1,
                        default => 2,
                    },
                    ($video['official'] ?? false) ? 0 : 1,
                ])
                ->first();

            if (! $video) {
                continue;
            }

            $candidates[] = [
                'id' => $movieId,
                'title' => $movie['title'] ?? '',
                'video_id' => $video['key'],
                'backdrop_url' => ! empty($movie['backdrop_path'])
                    ? 'https://image.tmdb.org/t/p/original'.$movie['backdrop_path']
                    : null,
            ];

            if (count($candidates) === 5) {
                break;
            }
        }

        return $candidates;
    }

    public function posterUrl(?string $path, string $size = 'w500'): ?string
    {
        return $path ? "https://image.tmdb.org/t/p/{$size}{$path}" : null;
    }

    private function movieVideos(int $tmdbId, string $language): array
    {
        return Cache::remember(
            "tmdb:movie:{$tmdbId}:videos:{$language}",
            self::VIDEO_CACHE_SECONDS,
            fn (): array => $this->client()
                ->get("/movie/{$tmdbId}/videos", ['language' => $language])
                ->throw()
                ->json('results', []),
        );
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
