<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\AuditTrailResource;
use App\Http\Resources\CalibrationResource;
use App\Http\Resources\ItemCollection;
use App\Http\Resources\ItemResource;
use App\Http\Resources\ItemUnitResource;
use App\Http\Resources\MaintenanceResource;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\UsageResource;
use App\Models\AuditTrail;
use App\Models\BorrowingItem;
use App\Models\Item;
use App\Models\ItemCalibration;
use App\Models\ItemMaintenance;
use App\Models\StockMovement;
use App\Models\Usage;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * Eager loads category and creator to prevent N+1 queries
     */
    public function index(Request $request)
    {
        // Get query parameters for filtering
        $query = Item::query()
            ->with(['category', 'creator', 'location'])
            ->withCount([
                'units',
                'units as available_units_count' => fn ($q) => $q->available(),
                'calibrations',
                'maintenances',
                'borrowings',
                'usages',
            ])
            ->when($request->query('type'), function ($q, string $type) {
                return $q->whereHas('category', fn ($category) => $category->where('type', $type));
            })
            ->when($request->query('condition_status'), function ($q, string $status) {
                return $q->whereHas('units', fn ($units) => $units->where('condition', $status));
            })
            ->when($request->boolean('low_stock'), function ($q) {
                return $q->whereColumn('stock_quantity', '<', 'minimum_stock');
            })
            ->when($request->boolean('needs_calibration'), function ($q) {
                return $q->whereHas('units', function ($units) {
                    $units->whereNotNull('next_calibration_date')
                        ->where('next_calibration_date', '<', now()->toDateString());
                });
            })
            ->when($request->boolean('expired'), function ($q) {
                return $q->whereHas('units', function ($units) {
                    $units->whereNotNull('expiry_date')
                        ->where('expiry_date', '<', now()->toDateString());
                });
            })
            ->when($request->query('search'), function ($q, string $search) {
                return $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), function ($q) use ($request) {
                return $q->where('category_id', $request->get('category_id'));
            });

        // Get sort parameters
        $sortBy = $request->query('sort_by', 'created_at');
        $sortOrder = $request->query('sort_order', 'desc');

        // Validate sort column to prevent injection
        $allowedSortColumns = ['id', 'name', 'code', 'stock_quantity', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderByDesc('created_at');
        }

        // Paginate results
        $perPage = $request->query('per_page', 15);
        $items = $query->paginate($perPage);

        return new ItemCollection($items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemRequest $request)
    {
        $item = Item::create($request->validated());

        // Load relationships for the response
        $item->load(['category', 'creator', 'location'])->loadCount(['calibrations', 'maintenances', 'borrowings', 'usages']);

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     *
     * Eager loads category and creator to prevent N+1 queries
     */
    public function show(Item $item)
    {
        $item->load(['category', 'creator', 'location'])->loadCount([
            'units',
            'units as available_units_count' => fn ($q) => $q->available(),
            'calibrations',
            'maintenances',
            'borrowings',
            'usages',
        ]);

        return new ItemResource($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        // Reload with relationships for the response
        $item->load(['category', 'creator', 'location'])->loadCount(['calibrations', 'maintenances', 'borrowings', 'usages']);

        return new ItemResource($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return response()->json(null, 204);
    }

    public function stockMovements(Item $item, Request $request)
    {
        $movements = $item->stockMovements()
            ->with(['itemUnit', 'performer'])
            ->orderBy('occurred_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return StockMovementResource::collection($movements);
    }

    public function usages(Item $item, Request $request)
    {
        $usages = $item->usages()
            ->with(['user', 'itemUnit', 'verifiedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return UsageResource::collection($usages);
    }

    public function calibrations(Item $item, Request $request)
    {
        $calibrations = $item->calibrations()
            ->with(['itemUnit', 'recorder'])
            ->orderBy('calibration_date', 'desc')
            ->paginate($request->query('per_page', 15));

        return CalibrationResource::collection($calibrations);
    }

    public function maintenances(Item $item, Request $request)
    {
        $maintenances = $item->maintenances()
            ->with(['itemUnit', 'recorder'])
            ->orderBy('maintenance_date', 'desc')
            ->paginate($request->query('per_page', 15));

        return MaintenanceResource::collection($maintenances);
    }

    public function auditTrails(Item $item, Request $request)
    {
        $unitIds = $item->units()->pluck('id');

        $audits = AuditTrail::query()
            ->where(function ($query) use ($item, $unitIds) {
                $query
                    ->where(function ($q) use ($item) {
                        $q->where('auditable_type', Item::class)
                            ->where('auditable_id', $item->id);
                    })
                    ->orWhere(function ($q) use ($item) {
                        $q->where('auditable_type', BorrowingItem::class)
                            ->whereIn('auditable_id', BorrowingItem::query()->select('id')->where('item_id', $item->id));
                    })
                    ->orWhere(function ($q) use ($item) {
                        $q->where('auditable_type', Usage::class)
                            ->whereIn('auditable_id', Usage::query()->select('id')->where('item_id', $item->id));
                    })
                    ->orWhere(function ($q) use ($item) {
                        $q->where('auditable_type', StockMovement::class)
                            ->whereIn('auditable_id', StockMovement::query()->select('id')->where('item_id', $item->id));
                    })
                    ->orWhere(function ($q) use ($unitIds) {
                        $q->where('auditable_type', ItemCalibration::class)
                            ->whereIn('auditable_id', ItemCalibration::query()->select('id')->whereIn('item_unit_id', $unitIds));
                    })
                    ->orWhere(function ($q) use ($unitIds) {
                        $q->where('auditable_type', ItemMaintenance::class)
                            ->whereIn('auditable_id', ItemMaintenance::query()->select('id')->whereIn('item_unit_id', $unitIds));
                    });
            })
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return AuditTrailResource::collection($audits);
    }

    public function units(Item $item, Request $request)
    {
        $units = $item->units()
            ->with(['item', 'location'])
            ->withExists('activeBorrowing as is_borrowed')
            ->when($request->boolean('available'), fn ($q) => $q->available())
            ->orderBy('created_at', 'desc')
            ->paginate($request->query('per_page', 15));

        return ItemUnitResource::collection($units);
    }
}
