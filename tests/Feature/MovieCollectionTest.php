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
        ->assertSee('テスト映画');
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
