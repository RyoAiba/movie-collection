<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveMovieReviewRequest;
use App\Http\Requests\StoreMovieRequest;
use App\Http\Requests\UpdateMovieRequest;
use App\Models\Movie;
use App\Services\MovieCollectionService;
use App\Services\TmdbService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class MovieController extends Controller
{
    public function __construct(
        private readonly TmdbService $tmdb,
        private readonly MovieCollectionService $collection,
    ) {}

    public function home(): View
    {
        $movies = [];
        $apiError = null;
        $popularMovies = [];
        $popularError = null;
        $trailerMovies = [];
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

        if (! empty($movies)) {
            $trailerMovies = $this->tmdb->trailerCarouselCandidates($movies);
        }

        $collection = Movie::query()->latest()->get();
        $collectedMovies = $collection->pluck('id', 'tmdb_id');
        $collectionMovies = $collection->map(fn (Movie $movie): array => [
            'id' => $movie->tmdb_id,
            'title' => $movie->title,
            'poster_path' => $movie->poster_path,
            'release_date' => $movie->release_date?->toDateString(),
        ])->all();

        return view('movies.home', compact('movies', 'popularMovies', 'collectionMovies', 'trailerMovies', 'fiscalYear', 'collectedMovies', 'apiError', 'popularError'));
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

        $collectionMovie = $this->collection->findByTmdbId($tmdbId);
        $collectionId = $collectionMovie?->id;
        $director = collect($movie['credits']['crew'] ?? [])->firstWhere('job', 'Director');
        $cast = collect($movie['credits']['cast'] ?? [])->take(10);
        $trailer = $this->tmdb->preferredVideo($movie['videos']['results'] ?? [], allowTeaser: false);

        try {
            $tmdbReviews = collect($this->tmdb->movieReviews($tmdbId))->take(10);
        } catch (ConnectionException|RequestException) {
            $tmdbReviews = collect();
        }

        return view('movies.show', compact('movie', 'collectionMovie', 'collectionId', 'director', 'cast', 'trailer', 'tmdbReviews'));
    }

    public function store(StoreMovieRequest $request): RedirectResponse|JsonResponse
    {
        $tmdbId = $request->integer('tmdb_id');
        $movie = $this->collection->findByTmdbId($tmdbId);

        if ($movie) {
            return $this->collectionStoredResponse($request, $movie, false);
        }

        try {
            $tmdbMovie = $this->tmdb->movie($tmdbId);
        } catch (ConnectionException|RequestException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '映画情報を取得できなかったため、追加できませんでした。',
                ], 502);
            }

            return back()->with('error', '映画情報を取得できなかったため、追加できませんでした。');
        }

        $movie = $this->collection->firstOrCreateFromTmdb($tmdbMovie);

        return $this->collectionStoredResponse($request, $movie, $movie->wasRecentlyCreated);
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

    public function saveReview(SaveMovieReviewRequest $request, int $tmdbId): JsonResponse
    {
        $movie = $this->collection->findByTmdbId($tmdbId);

        if (! $movie) {
            try {
                $tmdbMovie = $this->tmdb->movie($tmdbId);
            } catch (ConnectionException|RequestException) {
                return response()->json([
                    'message' => '映画情報を取得できなかったため、レビューを保存できませんでした。',
                ], 502);
            }

            $movie = $this->collection->firstOrCreateFromTmdb($tmdbMovie);
        }

        $movie->update($request->validated());

        return response()->json([
            'message' => 'レビューを保存しました。',
            'movie_id' => $movie->id,
            'tmdb_id' => $movie->tmdb_id,
            'destroy_url' => route('movies.destroy', $movie),
        ]);
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

    private function collectionStoredResponse(StoreMovieRequest $request, Movie $movie, bool $created): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $created ? 'コレクションに追加しました。' : 'コレクションに追加済みです。',
                'movie_id' => $movie->id,
                'tmdb_id' => $movie->tmdb_id,
                'destroy_url' => route('movies.destroy', $movie),
            ], $created ? 201 : 200);
        }

        return back()->with(
            'success',
            $created ? '「'.$movie->title.'」をコレクションに追加しました。' : '「'.$movie->title.'」は追加済みです。',
        );
    }
}
