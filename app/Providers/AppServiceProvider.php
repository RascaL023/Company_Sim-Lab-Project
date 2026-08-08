<?php

namespace App\Providers;

use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\StockMovement;
use App\Models\Usage;
use App\Observers\AuditableObserver;
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

        $auditableObserver = AuditableObserver::class;

        BorrowingRequest::observe($auditableObserver);
        BorrowingItem::observe($auditableObserver);
        Usage::observe($auditableObserver);
        StockMovement::observe($auditableObserver);
        ItemMaintenance::observe($auditableObserver);
        ItemCalibration::observe($auditableObserver);
    }
}
