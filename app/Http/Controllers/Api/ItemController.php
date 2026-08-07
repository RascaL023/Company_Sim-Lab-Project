<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemCollection;
use App\Http\Resources\ItemResource;
use App\Models\Item;
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
            ->with(['category', 'creator']) // Prevent N+1 queries
            ->when($request->filled('type'), function ($q, $type) {
                return $q->where('type', $type);
            })
            ->when($request->filled('condition_status'), function ($q, $status) {
                return $q->where('condition_status', $status);
            })
            ->when($request->filled('low_stock'), function ($q) {
                return $q->whereColumn('stock_quantity', '<', 'minimum_stock');
            })
            ->when($request->filled('needs_calibration'), function ($q) {
                return $q->whereNotNull('next_calibration_date')
                    ->where('next_calibration_date', '<', now()->toDateString());
            })
            ->when($request->filled('expired'), function ($q) {
                return $q->whereNotNull('expiry_date')
                    ->where('expiry_date', '<', now()->toDateString());
            })
            ->when($request->filled('search'), function ($q, $search) {
                return $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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
        $item->load(['category', 'creator']);

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
        $item->load(['category', 'creator']);

        return new ItemResource($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());

        // Reload with relationships for the response
        $item->load(['category', 'creator']);

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
}
