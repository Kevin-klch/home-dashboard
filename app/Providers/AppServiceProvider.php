<?php

namespace App\Providers;

use App\Services\Music\NowPlayingProvider;
use App\Services\Music\SpotifyClient;
use App\Services\Weather\OpenMeteoProvider;
use App\Services\Weather\WeatherProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Eine andere Wetterquelle lässt sich hier austauschen, ohne dass das
        // Widget angefasst werden muss.
        $this->app->bind(WeatherProvider::class, OpenMeteoProvider::class);

        // Läuft später etwas anderes als Spotify – etwa Last.fm –, wird hier
        // getauscht und die Anzeige bleibt unberührt.
        $this->app->bind(NowPlayingProvider::class, SpotifyClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
