<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (App::environment('production')) {
            URL::forceScheme('https');
        }
        config(['app.locale' => 'id']);
        Carbon::setLocale(config('app.locale'));
        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);
        // Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

    }
}
