<?php

namespace App\Providers;

use App\Models\BorrowingRequest;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Observers\StockMovementObserver;
use App\Policies\BorrowingRequestPolicy;
use App\Policies\UsagePolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(BorrowingRequest::class, BorrowingRequestPolicy::class);
        Gate::policy(Usage::class, UsagePolicy::class);

        StockMovement::observe(StockMovementObserver::class);
    }
}
