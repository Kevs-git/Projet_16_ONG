<?php

namespace App\Providers;

use App\Models\Update;
use App\Observers\UpdateObserver;
use Illuminate\Support\ServiceProvider;

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
        // Register Update Observer for FCM notifications
        Update::observe(UpdateObserver::class);
    }
}
