<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CalibrationResource;
use App\Http\Resources\ItemUnitResource;
use App\Http\Resources\MaintenanceResource;
use App\Models\Item;
use App\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    public function store(Request $request)
    {
        Gate::authorize('create', ItemUnit::class);

        $validated = $request->validate([
            'item_id' => [
                'required',
                'exists:items,id',
                function ($attribute, $value, $fail) {
                    $item = Item::find($value);

                    if ($item && $item->isBahan()) {
                        $fail('Item bahan tidak dapat memiliki unit fisik.');
                    }
                },
            ],
            'serial_number' => 'required|string|max:100|unique:item_units,serial_number',
            'asset_tag' => 'nullable|string|max:50|unique:item_units,asset_tag',
            'condition' => 'required|in:baik,rusak_ringan,rusak_berat,hilang,dihapus',
            'location_id' => 'nullable|exists:locations,id',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'last_calibration_date' => 'nullable|date',
            'next_calibration_date' => 'nullable|date|after_or_equal:last_calibration_date',
            'notes' => 'nullable|string',
        ], [
            'item_id.required' => 'Item wajib dipilih.',
            'item_id.exists' => 'Item tidak ditemukan.',
            'serial_number.required' => 'Serial number wajib diisi.',
            'serial_number.unique' => 'Serial number sudah digunakan.',
            'asset_tag.unique' => 'Asset tag sudah digunakan.',
            'condition.in' => 'Kondisi yang dipilih tidak valid.',
            'location_id.exists' => 'Lokasi yang dipilih tidak valid.',
            'expiry_date.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh sebelum tanggal pembelian.',
            'next_calibration_date.after_or_equal' => 'Tanggal kalibrasi berikutnya tidak boleh sebelum tanggal kalibrasi terakhir.',
        ]);

        $itemUnit = ItemUnit::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        $itemUnit->load(['item', 'location']);

        return (new ItemUnitResource($itemUnit))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ItemUnit $itemUnit)
    {
        $itemUnit->load(['item', 'location']);

        return new ItemUnitResource($itemUnit);
    }

    public function update(Request $request, ItemUnit $itemUnit)
    {
        Gate::authorize('update', $itemUnit);

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
        Gate::authorize('delete', $itemUnit);

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
