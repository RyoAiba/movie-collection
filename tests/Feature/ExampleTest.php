<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('the application returns a successful response', function () {
    Http::fake([
        'api.themoviedb.org/*' => Http::response(['results' => []]),
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
});
