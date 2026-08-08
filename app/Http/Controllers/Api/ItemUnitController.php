<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItemUnitResource;
use App\Models\ItemUnit;
use Illuminate\Http\Request;

class ItemUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemUnit::with('item.calibrations', 'item.maintenances');

        if ($request->filled('condition')) {
            $query->where('condition', $request->get('condition'));
        }

        if ($request->filled('needs_calibration')) {
            $query->whereNotNull('next_calibration_date')
                ->where('next_calibration_date', '<=', now()->addDays(30));
        }

        return $query->paginate($request->query('per_page', 15));
    }

    public function show(ItemUnit $itemUnit)
    {
        return new ItemUnitResource($itemUnit);
    }

    public function update(Request $request, ItemUnit $itemUnit)
    {
        $itemUnit->update($request->only([
            'condition', 'location', 'notes', 'last_calibration_date', 'next_calibration_date',
        ]));

        return new ItemUnitResource($itemUnit);
    }

    public function destroy(ItemUnit $itemUnit)
    {
        $itemUnit->delete();

        return response()->json(null, 204);
    }

    public function calibrations(ItemUnit $itemUnit)
    {
        return $itemUnit->calibrations()->paginate(15);
    }

    public function maintenances(ItemUnit $itemUnit)
    {
        return $itemUnit->maintenances()->paginate(15);
    }
}
