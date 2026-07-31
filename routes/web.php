<?php

use App\Http\Controllers\MovieController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MovieController::class, 'home'])->name('home');
Route::get('/movies/{tmdbId}', [MovieController::class, 'show'])->whereNumber('tmdbId')->name('movies.show');
Route::put('/movies/{tmdbId}/review', [MovieController::class, 'saveReview'])->whereNumber('tmdbId')->name('movies.review.save');
Route::get('/collection', [MovieController::class, 'index'])->name('movies.index');
Route::post('/collection', [MovieController::class, 'store'])->name('movies.store');
Route::delete('/collection/{movie}', [MovieController::class, 'destroy'])->name('movies.destroy');
