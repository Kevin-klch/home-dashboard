<?php

use App\Http\Controllers\SpotifyController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('musik', 'music')->name('music');

    // Weiterleitungs-URL im Spotify-Dashboard: http://127.0.0.1:8000/spotify/callback
    Route::get('spotify/verbinden', [SpotifyController::class, 'connect'])->name('spotify.connect');
    Route::get('spotify/callback', [SpotifyController::class, 'callback'])->name('spotify.callback');
    Route::delete('spotify', [SpotifyController::class, 'disconnect'])->name('spotify.disconnect');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
