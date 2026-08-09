<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalibrationResource;
use App\Models\ItemCalibration;
use Illuminate\Http\Request;

class CalibrationController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemCalibration::with(['itemUnit.item.category']);

        if ($request->filled('needs_calibration')) {
            $query->where('next_calibration_date', '<=', now()->addDays(30));
        }

        return CalibrationResource::collection(
            $query->orderBy('calibration_date', 'desc')->paginate($request->query('per_page', 15))
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_unit_id' => 'required|exists:item_units,id',
            'calibration_date' => 'required|date',
            'next_calibration_date' => 'required|date|after:calibration_date',
            'calibrated_by' => 'required|string',
            'certificate_number' => 'nullable|string',
            'result' => 'required|in:lulus,tidak_lulus',
            'notes' => 'nullable|string',
        ]);

        $calibration = ItemCalibration::create($validated);

        $itemUnit = $calibration->itemUnit;
        $itemUnit->update([
            'last_calibration_date' => $calibration->calibration_date,
            'next_calibration_date' => $calibration->next_calibration_date,
        ]);

        return (new CalibrationResource($calibration))->response()->setStatusCode(201);
    }

    public function show(ItemCalibration $calibration)
    {
        return new CalibrationResource($calibration);
    }

    public function update(Request $request, ItemCalibration $calibration)
    {
        $calibration->update($request->only([
            'calibration_date', 'next_calibration_date', 'calibrated_by',
            'certificate_number', 'result', 'notes',
        ]));

        return new CalibrationResource($calibration);
    }
}
