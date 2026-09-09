<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
