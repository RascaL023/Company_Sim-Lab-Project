<?php

namespace App\Providers;

use App\Models\AssetDisposal;
use App\Models\BorrowingItem;
use App\Models\BorrowingRequest;
use App\Models\Category;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\Usage;
use App\Models\User;
use App\Observers\AuditableObserver;
use App\Observers\BorrowingItemObserver;
use App\Observers\StockMovementObserver;
use App\Policies\AssetDisposalPolicy;
use App\Policies\BorrowingItemPolicy;
use App\Policies\BorrowingRequestPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\StockOpnamePolicy;
use App\Policies\UsagePolicy;
use App\Policies\UserPolicy;
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
        Gate::policy(BorrowingItem::class, BorrowingItemPolicy::class);
        Gate::policy(Usage::class, UsagePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(StockOpname::class, StockOpnamePolicy::class);
        Gate::policy(AssetDisposal::class, AssetDisposalPolicy::class);

        StockMovement::observe(StockMovementObserver::class);
        BorrowingItem::observe(BorrowingItemObserver::class);

        $auditableObserver = AuditableObserver::class;

        BorrowingRequest::observe($auditableObserver);
        BorrowingItem::observe($auditableObserver);
        Usage::observe($auditableObserver);
        StockMovement::observe($auditableObserver);
        ItemMaintenance::observe($auditableObserver);
        ItemCalibration::observe($auditableObserver);
        AssetDisposal::observe($auditableObserver);
    }
}
