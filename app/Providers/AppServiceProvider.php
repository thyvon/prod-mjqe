<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;

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
        //
        Inertia::share([
            'auth' => fn () => ['user' => Auth::user()],
        ]);

        $this->app->make('events')->listen(
            SocialiteWasCalled::class,
            MicrosoftExtendSocialite::class
        );
        // Force HTTPS and set the root URL for production
        if ($this->app->environment('production')) {
            URL::forceRootUrl(config('app.url'));
        }
    }
}
