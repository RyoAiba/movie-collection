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
use Illuminate\View\View;

class MovieController extends Controller
{
    public function __construct(private readonly TmdbService $tmdb) {}

    public function home(): View
    {
        $movies = [];
        $apiError = null;

        try {
            $movies = $this->tmdb->nowPlaying();
        } catch (ConnectionException|RequestException) {
            $apiError = '上映中の映画を取得できませんでした。時間をおいて再度お試しください。';
        }

        $collectedMovies = Movie::query()->pluck('id', 'tmdb_id');

        return view('movies.home', compact('movies', 'collectedMovies', 'apiError'));
    }

    public function index(): View
    {
        $movies = Movie::query()->latest()->get();

        return view('movies.index', compact('movies'));
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
