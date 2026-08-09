<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalibrationResource;
use App\Http\Resources\ItemUnitResource;
use App\Http\Resources\MaintenanceResource;
use App\Models\ItemUnit;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemUnit::with(['item.calibrations', 'item.maintenances', 'location']);

        if ($request->filled('condition')) {
            $query->where('condition', $request->get('condition'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->get('location_id'));
        }

        if ($request->filled('needs_calibration')) {
            $query->whereNotNull('next_calibration_date')
                ->where('next_calibration_date', '<=', now()->addDays(30));
        }

        return ItemUnitResource::collection(
            $query->paginate($request->query('per_page', 15))
        );
    }

    public function show(ItemUnit $itemUnit)
    {
        $itemUnit->load(['item', 'location']);

        return new ItemUnitResource($itemUnit);
    }

    public function update(Request $request, ItemUnit $itemUnit)
    {
        $validated = $request->validate([
            'condition' => 'sometimes|in:baik,rusak_ringan,rusak_berat,hilang,dihapus',
            'location_id' => 'sometimes|nullable|exists:locations,id',
            'notes' => 'sometimes|nullable|string',
            'last_calibration_date' => 'sometimes|nullable|date',
            'next_calibration_date' => 'sometimes|nullable|date',
        ]);

        $itemUnit->update($validated);
        $itemUnit->load('location');

        return new ItemUnitResource($itemUnit);
    }

    public function destroy(ItemUnit $itemUnit)
    {
        $itemUnit->delete();

        return response()->json(null, 204);
    }

    public function calibrations(ItemUnit $itemUnit)
    {
        return CalibrationResource::collection(
            $itemUnit->calibrations()->paginate(15)
        );
    }

    public function maintenances(ItemUnit $itemUnit)
    {
        return MaintenanceResource::collection(
            $itemUnit->maintenances()->paginate(15)
        );
    }
}
