<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
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
        $uniqueMovies = collect(array_slice($movies, 0, 10))
            ->filter(fn (array $movie): bool => (int) ($movie['id'] ?? 0) > 0)
            ->unique(fn (array $movie): int => (int) $movie['id'])
            ->values();
        $movieIds = $uniqueMovies->pluck('id')->map(fn ($id): int => (int) $id)->all();

        if ($movieIds === []) {
            return [];
        }

        $language = (string) config('services.tmdb.language');
        $candidateCacheKey = 'tmdb:trailer-carousel:'.sha1($language.':'.implode(',', $movieIds));

        if (Cache::has($candidateCacheKey)) {
            return Cache::get($candidateCacheKey);
        }

        $localizedVideos = $this->cachedMovieVideos($movieIds, $language);
        $englishFallbackIds = array_values(array_filter(
            $movieIds,
            fn (int $movieId): bool => ! $this->preferredVideo($localizedVideos[$movieId] ?? [], allowTeaser: false),
        ));
        $englishVideos = $this->cachedMovieVideos($englishFallbackIds, 'en-US');
        $candidates = [];

        foreach ($uniqueMovies as $movie) {
            $movieId = (int) $movie['id'];
            $videos = [
                ...($localizedVideos[$movieId] ?? []),
                ...($englishVideos[$movieId] ?? []),
            ];
            $video = $this->preferredVideo($videos);

            if ($video) {
                $candidates[] = [
                    'id' => $movieId,
                    'title' => $movie['title'] ?? '',
                    'video_id' => $video['key'],
                    'backdrop_url' => ! empty($movie['backdrop_path'])
                        ? 'https://image.tmdb.org/t/p/original'.$movie['backdrop_path']
                        : null,
                ];
            }

            if (count($candidates) === 5) {
                break;
            }
        }

        Cache::put($candidateCacheKey, $candidates, $candidates === [] ? 600 : self::VIDEO_CACHE_SECONDS);

        return $candidates;
    }

    public function preferredVideo(array $videos, bool $allowTeaser = true): ?array
    {
        $allowedTypes = $allowTeaser ? ['Trailer', 'Teaser'] : ['Trailer'];

        return collect($videos)
            ->filter(fn (array $video): bool => ($video['site'] ?? null) === 'YouTube'
                && in_array($video['type'] ?? null, $allowedTypes, true)
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
    }

    public function posterUrl(?string $path, string $size = 'w500'): ?string
    {
        return $path ? "https://image.tmdb.org/t/p/{$size}{$path}" : null;
    }

    /**
     * @return array<int, array>
     */
    private function cachedMovieVideos(array $movieIds, string $language): array
    {
        $videos = [];
        $missingIds = [];

        foreach ($movieIds as $movieId) {
            $cacheKey = $this->videoCacheKey($movieId, $language);

            if (Cache::has($cacheKey)) {
                $videos[$movieId] = Cache::get($cacheKey);
            } else {
                $missingIds[] = $movieId;
            }
        }

        if ($missingIds === []) {
            return $videos;
        }

        try {
            $responses = Http::pool(fn (Pool $pool): array => array_map(
                fn (int $movieId) => $pool
                    ->as((string) $movieId)
                    ->withToken((string) config('services.tmdb.token'))
                    ->acceptJson()
                    ->timeout(10)
                    ->get(self::BASE_URL."/movie/{$movieId}/videos", ['language' => $language]),
                $missingIds,
            ));
        } catch (ConnectionException) {
            return $videos;
        }

        foreach ($missingIds as $movieId) {
            $response = $responses[(string) $movieId] ?? null;

            if (! $response instanceof Response || ! $response->successful()) {
                continue;
            }

            $movieVideos = $response->json('results', []);
            $videos[$movieId] = $movieVideos;
            Cache::put($this->videoCacheKey($movieId, $language), $movieVideos, self::VIDEO_CACHE_SECONDS);
        }

        return $videos;
    }

    private function videoCacheKey(int $tmdbId, string $language): string
    {
        return "tmdb:movie:{$tmdbId}:videos:{$language}";
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
