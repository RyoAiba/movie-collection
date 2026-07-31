<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Models\Movie;
use App\Services\TmdbService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function __construct(private readonly TmdbService $tmdb) {}

    public function home(): View
    {
        $movies = [];
        $apiError = null;
        $popularMovies = [];
        $popularError = null;
        $today = now();
        $fiscalYear = $this->tmdb->fiscalYearPeriod($today)['year'];

        try {
            $movies = $this->tmdb->nowPlaying();
        } catch (ConnectionException|RequestException) {
            $apiError = '上映中の映画を取得できませんでした。時間をおいて再度お試しください。';
        }

        try {
            $popularMovies = $this->tmdb->popularMovies($today);
        } catch (ConnectionException|RequestException) {
            $popularError = '人気作品を取得できませんでした。時間をおいて再度お試しください。';
        }

        $collection = Movie::query()->latest()->get();
        $collectedMovies = $collection->pluck('id', 'tmdb_id');
        $collectionMovies = $collection->map(fn (Movie $movie): array => [
            'id' => $movie->tmdb_id,
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'release_date' => $movie->release_date?->toDateString(),
        ])->all();

        return view('movies.home', compact('movies', 'popularMovies', 'collectionMovies', 'fiscalYear', 'collectedMovies', 'apiError', 'popularError'));
    }

    public function index(): View
    {
        $movies = Movie::query()->latest()->get();

        return view('movies.index', compact('movies'));
    }

    public function show(int $tmdbId): View|Response
    {
        try {
            $movie = $this->tmdb->movieDetails($tmdbId);
        } catch (RequestException $exception) {
            if ($exception->response->status() === 404) {
                abort(404);
            }

            return response()->view('movies.api-error', [
                'message' => '映画の詳細情報を取得できませんでした。',
            ], 502);
        } catch (ConnectionException) {
            return response()->view('movies.api-error', [
                'message' => 'TMDBに接続できませんでした。時間をおいて再度お試しください。',
            ], 502);
        }

        $collectionId = Movie::query()->where('tmdb_id', $tmdbId)->value('id');
        $director = collect($movie['credits']['crew'] ?? [])->firstWhere('job', 'Director');
        $cast = collect($movie['credits']['cast'] ?? [])->take(10);
        $trailer = collect($movie['videos']['results'] ?? [])
            ->filter(fn (array $video): bool => ($video['site'] ?? null) === 'YouTube' && ($video['type'] ?? null) === 'Trailer')
            ->sortBy(fn (array $video): int => match ($video['iso_639_1'] ?? null) {
                'ja' => 0,
                'en' => 1,
                default => 2,
            })
            ->first();

        return view('movies.show', compact('movie', 'collectionId', 'director', 'cast', 'trailer'));
    }

    public function store(StoreMovieRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $tmdbMovie = $this->tmdb->movie($request->integer('tmdb_id'));
        } catch (ConnectionException|RequestException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '映画情報を取得できなかったため、追加できませんでした。',
                ], 502);
            }

            return back()->with('error', '映画情報を取得できなかったため、追加できませんでした。');
        }

        $movie = Movie::query()->create([
            'tmdb_id' => $tmdbMovie['id'],
            'title' => $tmdbMovie['title'],
            'overview' => $tmdbMovie['overview'] ?: null,
            'poster_path' => $tmdbMovie['poster_path'] ?? null,
            'release_date' => $tmdbMovie['release_date'] ?: null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'コレクションに追加しました。',
                'movie_id' => $movie->id,
                'destroy_url' => route('movies.destroy', $movie),
            ], 201);
        }

        return back()->with('success', '「'.$tmdbMovie['title'].'」をコレクションに追加しました。');
    }

    public function edit(Movie $movie): View
    {
        return view('movies.edit', compact('movie'));
    }

    public function update(UpdateMovieRequest $request, Movie $movie): RedirectResponse
    {
        $movie->update($request->validated());

        return redirect()->route('movies.index')->with('success', '評価とレビューを更新しました。');
    }

    public function destroy(Movie $movie): RedirectResponse|JsonResponse
    {
        $title = $movie->title;
        $movie->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'コレクションから外しました。',
            ]);
        }

        return redirect()->route('movies.index')->with('success', '「'.$title.'」をコレクションから削除しました。');
    }
}
