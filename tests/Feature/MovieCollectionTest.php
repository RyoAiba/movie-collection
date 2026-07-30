<?php

use App\Models\Movie;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('上映中の映画をトップページに表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response([
            'results' => [[
                'id' => 123,
                'title' => 'テスト映画',
                'overview' => '映画の概要',
                'poster_path' => '/poster.jpg',
                'release_date' => '2026-07-01',
            ]],
        ]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('上映中の映画')
        ->assertSee('テスト映画')
        ->assertSee(route('movies.show', 123))
        ->assertDontSee('映画の概要');
});

test('映画詳細と出演者と日本語予告編を表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123*' => Http::response([
            'id' => 123,
            'title' => '日本語タイトル',
            'original_title' => 'Original Title',
            'overview' => '詳しいあらすじ',
            'poster_path' => '/poster.jpg',
            'backdrop_path' => '/backdrop.jpg',
            'release_date' => '2026-07-01',
            'runtime' => 125,
            'vote_average' => 8.2,
            'genres' => [['id' => 1, 'name' => 'ドラマ']],
            'credits' => [
                'crew' => [['job' => 'Director', 'name' => 'テスト監督']],
                'cast' => [[
                    'name' => 'テスト俳優',
                    'character' => '主人公',
                    'profile_path' => '/actor.jpg',
                ]],
            ],
            'videos' => [
                'results' => [
                    ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'en', 'key' => 'english-key'],
                    ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'ja', 'key' => 'japanese-key'],
                ],
            ],
        ]),
    ]);

    $this->get('/movies/123')
        ->assertOk()
        ->assertSee('日本語タイトル')
        ->assertSee('Original Title')
        ->assertSee('2時間5分')
        ->assertSee('テスト監督')
        ->assertSee('テスト俳優')
        ->assertSee('主人公')
        ->assertSee('youtube-nocookie.com/embed/japanese-key');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.themoviedb.org/3/movie/123')
        && $request['language'] === config('services.tmdb.language')
        && $request['append_to_response'] === 'credits,videos');
});

test('存在しないTMDB映画は404を返す', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/999999*' => Http::response([], 404),
    ]);

    $this->get('/movies/999999')->assertNotFound();
});

test('TMDB詳細取得の障害時はエラー画面を返す', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123*' => Http::response([], 500),
    ]);

    $this->get('/movies/123')
        ->assertStatus(502)
        ->assertSee('映画の詳細情報を取得できませんでした。');
});

test('TMDBの詳細情報から映画をコレクションに追加できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123*' => Http::response([
            'id' => 123,
            'title' => 'テスト映画',
            'overview' => '映画の概要',
            'poster_path' => '/poster.jpg',
            'release_date' => '2026-07-01',
        ]),
    ]);

    $this->post('/collection', ['tmdb_id' => 123])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->assertDatabaseHas('movies', [
        'tmdb_id' => 123,
        'title' => 'テスト映画',
    ]);
});

test('映画を非同期で追加すると解除URLを返す', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/456*' => Http::response([
            'id' => 456,
            'title' => '非同期テスト映画',
            'overview' => '',
            'poster_path' => null,
            'release_date' => '',
        ]),
    ]);

    $response = $this->postJson('/collection', ['tmdb_id' => 456])
        ->assertCreated()
        ->assertJsonPath('message', 'コレクションに追加しました。');

    $movie = Movie::query()->where('tmdb_id', 456)->firstOrFail();

    $response->assertJsonPath('movie_id', $movie->id)
        ->assertJsonPath('destroy_url', route('movies.destroy', $movie));
});

test('追加済み映画を上映中一覧から非同期で解除できる', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 123,
        'title' => 'テスト映画',
    ]);

    $this->deleteJson(route('movies.destroy', $movie))
        ->assertOk()
        ->assertJsonPath('message', 'コレクションから外しました。');

    $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
});

test('評価は1から5の範囲で更新できる', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 123,
        'title' => 'テスト映画',
    ]);

    $this->put(route('movies.update', $movie), [
        'rating' => 5,
        'review' => 'とても良かったです。',
    ])->assertRedirect(route('movies.index'));

    expect($movie->fresh())
        ->rating->toBe(5)
        ->review->toBe('とても良かったです。');

    $this->put(route('movies.update', $movie), ['rating' => 6])
        ->assertSessionHasErrors('rating');
});

test('映画をコレクションから削除できる', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 123,
        'title' => 'テスト映画',
    ]);

    $this->delete(route('movies.destroy', $movie))
        ->assertRedirect(route('movies.index'));

    $this->assertDatabaseMissing('movies', ['id' => $movie->id]);
});
