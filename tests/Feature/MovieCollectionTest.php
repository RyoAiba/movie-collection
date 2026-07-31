<?php

use App\Models\Movie;
use App\Services\TmdbService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('上映中の映画をトップページに表示できる', function () {
    $this->travelTo(CarbonImmutable::parse('2026-07-30'));

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
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'results' => [[
                'id' => 456,
                'title' => '人気のテスト映画',
                'poster_path' => '/popular.jpg',
                'release_date' => '2026-07-02',
            ]],
        ]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('上映中の映画')
        ->assertSee('テスト映画')
        ->assertSee(route('movies.show', 123))
        ->assertSee('2026年の人気作品')
        ->assertSee('人気のテスト映画')
        ->assertSee(route('movies.show', 456))
        ->assertDontSee('映画の概要');

    Http::assertSent(fn ($request) => str_starts_with($request->url(), 'https://api.themoviedb.org/3/discover/movie')
        && $request['language'] === config('services.tmdb.language')
        && $request['region'] === config('services.tmdb.region')
        && $request['primary_release_date.gte'] === '2026-04-01'
        && $request['primary_release_date.lte'] === '2026-07-30'
        && $request['sort_by'] === 'popularity.desc');
});

test('人気映画の取得失敗時も上映中映画を表示できる', function () {
    $this->travelTo(CarbonImmutable::parse('2026-07-30'));

    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response([
            'results' => [[
                'id' => 123,
                'title' => '上映中のテスト映画',
                'poster_path' => null,
                'release_date' => '2026-07-01',
            ]],
        ]),
        'api.themoviedb.org/3/discover/movie*' => Http::response([], 500),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('上映中のテスト映画')
        ->assertSee('人気作品を取得できませんでした。');
});

test('カルーセル候補はYouTubeのTrailerをTeaserより優先する', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/8123/videos*' => Http::response([
            'results' => [
                ['site' => 'Vimeo', 'type' => 'Trailer', 'key' => 'vimeo-trailer', 'iso_639_1' => 'ja', 'official' => true],
                ['site' => 'YouTube', 'type' => 'Teaser', 'key' => 'youtube-teaser', 'iso_639_1' => 'ja', 'official' => true],
                ['site' => 'YouTube', 'type' => 'Trailer', 'key' => 'youtube-trailer', 'iso_639_1' => 'ja', 'official' => true],
            ],
        ]),
    ]);

    $candidates = app(TmdbService::class)->trailerCarouselCandidates([[
        'id' => 8123,
        'title' => '予告編あり映画',
        'backdrop_path' => '/backdrop.jpg',
    ]]);

    expect($candidates)->toHaveCount(1)
        ->and($candidates[0]['video_id'])->toBe('youtube-trailer');

    app(TmdbService::class)->trailerCarouselCandidates([[
        'id' => 8123,
        'title' => '予告編あり映画',
        'backdrop_path' => '/backdrop.jpg',
    ]]);

    Http::assertSentCount(1);
});

test('カルーセル候補は英語より日本語の動画を優先する', function () {
    Http::fake(function (Request $request) {
        $language = $request['language'];

        return Http::response([
            'results' => [[
                'site' => 'YouTube',
                'type' => 'Trailer',
                'key' => $language === 'en-US' ? 'english-trailer' : 'japanese-trailer',
                'iso_639_1' => $language === 'en-US' ? 'en' : 'ja',
                'official' => true,
            ]],
        ]);
    });

    $candidates = app(TmdbService::class)->trailerCarouselCandidates([[
        'id' => 8234,
        'title' => '多言語映画',
        'backdrop_path' => '/backdrop.jpg',
    ]]);

    expect($candidates[0]['video_id'])->toBe('japanese-trailer');
});

test('日本語にTrailerがない場合だけ英語へフォールバックする', function () {
    Http::fake(function (Request $request) {
        $language = $request['language'];

        return Http::response([
            'results' => [[
                'site' => 'YouTube',
                'type' => $language === 'en-US' ? 'Trailer' : 'Teaser',
                'key' => $language === 'en-US' ? 'english-trailer' : 'japanese-teaser',
                'iso_639_1' => $language === 'en-US' ? 'en' : 'ja',
                'official' => true,
            ]],
        ]);
    });

    $candidates = app(TmdbService::class)->trailerCarouselCandidates([[
        'id' => 8345,
        'title' => 'フォールバック映画',
        'backdrop_path' => '/backdrop.jpg',
    ]]);

    expect($candidates[0]['video_id'])->toBe('english-trailer');
    Http::assertSentCount(2);
});

test('予告編候補が0件でもトップページを表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response([
            'results' => [[
                'id' => 9123,
                'title' => '予告編なし映画',
                'poster_path' => '/poster.jpg',
                'backdrop_path' => '/backdrop.jpg',
                'release_date' => '2026-07-01',
            ]],
        ]),
        'api.themoviedb.org/3/movie/9123/videos*' => Http::response(['results' => []]),
        'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => []]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('上映中の映画')
        ->assertSee('予告編なし映画')
        ->assertDontSee('trailer-carousel', false);
});

test('動画取得失敗時も既存カテゴリを表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response([
            'results' => [[
                'id' => 9345,
                'title' => '上映中作品',
                'poster_path' => '/poster.jpg',
                'backdrop_path' => '/backdrop.jpg',
                'release_date' => '2026-07-01',
            ]],
        ]),
        'api.themoviedb.org/3/movie/9345/videos*' => Http::failedConnection(),
        'api.themoviedb.org/3/discover/movie*' => Http::response([
            'results' => [[
                'id' => 456,
                'title' => '人気作品',
                'poster_path' => '/popular.jpg',
                'release_date' => '2026-07-02',
            ]],
        ]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('上映中作品')
        ->assertSee('人気作品')
        ->assertSee('マイコレクション');
});

test('人気作品が0件の場合はカテゴリを表示しない', function () {
    $this->travelTo(CarbonImmutable::parse('2026-07-30'));

    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response(['results' => []]),
        'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => []]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('2026年の人気作品');
});

test('トップページにマイコレクションを共通カードで表示する', function () {
    $this->travelTo(CarbonImmutable::parse('2026-07-30'));

    $movie = Movie::query()->create([
        'tmdb_id' => 789,
        'title' => '保存済み映画',
        'poster_path' => '/saved.jpg',
        'release_date' => '2026-05-01',
    ]);

    Http::fake([
        'api.themoviedb.org/3/movie/now_playing*' => Http::response(['results' => []]),
        'api.themoviedb.org/3/discover/movie*' => Http::response(['results' => []]),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('マイコレクション')
        ->assertSee('保存済み映画')
        ->assertSee(route('movies.show', $movie->tmdb_id))
        ->assertSee('aria-pressed="true"', false);
});

test('コレクションを縦一覧で表示しTMDB平均評価を星の割合で表示する', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 7771,
        'title' => '評価付きコレクション映画',
        'poster_path' => '/collection.jpg',
        'release_date' => '2026-07-31',
    ]);

    Http::fake([
        'api.themoviedb.org/3/movie/7771*' => Http::response([
            'id' => 7771,
            'vote_average' => 8.2,
        ]),
    ]);

    $this->get('/collection')
        ->assertOk()
        ->assertSee('評価付きコレクション映画')
        ->assertSee('82%')
        ->assertSee('★★★★★')
        ->assertSee(route('movies.show', $movie->tmdb_id))
        ->assertSee('aria-pressed="true"', false)
        ->assertSee('コレクションから外す')
        ->assertDontSee('映画を探す')
        ->assertDontSee('>編集<', false)
        ->assertDontSee('コレクションから削除');

    $this->get('/collection')->assertOk();
    Http::assertSentCount(1);
});

test('平均評価の取得失敗時もコレクションを表示できる', function () {
    Movie::query()->create([
        'tmdb_id' => 7772,
        'title' => '評価取得失敗映画',
    ]);

    Http::fake([
        'api.themoviedb.org/3/movie/7772*' => Http::failedConnection(),
    ]);

    $this->get('/collection')
        ->assertOk()
        ->assertSee('評価取得失敗映画')
        ->assertSee('評価情報なし');
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
        && ($request['append_to_response'] ?? null) === 'credits,videos');
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

test('映画詳細にTMDBレビューを表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123/reviews*' => Http::response([
            'results' => [[
                'author' => 'レビュー投稿者',
                'author_details' => ['rating' => 8.0],
                'content' => 'TMDBから取得したレビュー本文です。',
            ]],
        ]),
        'api.themoviedb.org/3/movie/123*' => Http::response([
            'id' => 123,
            'title' => 'レビュー対象映画',
            'original_title' => 'Review Movie',
            'overview' => '',
            'poster_path' => null,
            'backdrop_path' => null,
            'release_date' => '',
            'runtime' => null,
            'genres' => [],
            'credits' => ['crew' => [], 'cast' => []],
            'videos' => ['results' => []],
        ]),
    ]);

    $this->get('/movies/123')
        ->assertOk()
        ->assertSee('あなたのレビュー')
        ->assertSee('みんなのレビュー')
        ->assertSee('レビュー投稿者')
        ->assertSee('TMDBから取得したレビュー本文です。');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/movie/123/reviews')
        && $request['language'] === config('services.tmdb.language'));
});

test('TMDBレビュー取得に失敗しても映画詳細を表示できる', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/123/reviews*' => Http::response([], 500),
        'api.themoviedb.org/3/movie/123*' => Http::response([
            'id' => 123,
            'title' => '詳細表示できる映画',
            'original_title' => 'Movie',
            'overview' => '',
            'poster_path' => null,
            'backdrop_path' => null,
            'release_date' => '',
            'runtime' => null,
            'genres' => [],
            'credits' => ['crew' => [], 'cast' => []],
            'videos' => ['results' => []],
        ]),
    ]);

    $this->get('/movies/123')
        ->assertOk()
        ->assertSee('詳細表示できる映画')
        ->assertSee('レビューはまだありません');
});

test('詳細画面から評価とレビューを非同期保存できる', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 123,
        'title' => '保存済み映画',
    ]);

    $this->putJson(route('movies.review.save', 123), [
        'rating' => 4,
        'review' => '詳細画面から保存しました。',
    ])
        ->assertOk()
        ->assertJsonPath('message', 'レビューを保存しました。')
        ->assertJsonPath('destroy_url', route('movies.destroy', $movie));

    expect($movie->fresh())
        ->rating->toBe(4)
        ->review->toBe('詳細画面から保存しました。');
});

test('未保存映画のレビュー保存時にコレクションへ追加する', function () {
    Http::fake([
        'api.themoviedb.org/3/movie/456*' => Http::response([
            'id' => 456,
            'title' => '新規レビュー映画',
            'overview' => '概要',
            'poster_path' => '/poster.jpg',
            'release_date' => '2026-07-01',
        ]),
    ]);

    $this->putJson(route('movies.review.save', 456), [
        'rating' => 5,
        'review' => 'お気に入りです。',
    ])->assertOk();

    $this->assertDatabaseHas('movies', [
        'tmdb_id' => 456,
        'rating' => 5,
        'review' => 'お気に入りです。',
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

test('追加済み映画への重複リクエストを正常な追加済みレスポンスとして扱う', function () {
    $movie = Movie::query()->create([
        'tmdb_id' => 567,
        'title' => '追加済み映画',
    ]);

    $this->postJson('/collection', ['tmdb_id' => 567])
        ->assertOk()
        ->assertJsonPath('message', 'コレクションに追加済みです。')
        ->assertJsonPath('tmdb_id', 567)
        ->assertJsonPath('destroy_url', route('movies.destroy', $movie));

    expect(Movie::query()->where('tmdb_id', 567)->count())->toBe(1);
    Http::assertNothingSent();
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
