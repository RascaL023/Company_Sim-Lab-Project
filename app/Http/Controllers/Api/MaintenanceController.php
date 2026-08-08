<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MaintenanceResource;
use App\Models\ItemMaintenance;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $query = ItemMaintenance::with('itemUnit.item.category');

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        return $query->orderBy('maintenance_date', 'desc')->paginate($request->query('per_page', 15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_unit_id' => 'required|exists:item_units,id',
            'maintenance_date' => 'required|date',
            'description' => 'required|string',
            'performed_by' => 'required|string',
            'cost' => 'nullable|numeric',
            'status' => 'in:selesai,proses,tertunda',
            'notes' => 'nullable|string',
        ]);

        $maintenance = ItemMaintenance::create($validated);

        if ($maintenance->status === 'selesai') {
            $maintenance->itemUnit->update(['condition' => 'baik']);
        }

        return (new MaintenanceResource($maintenance))->response()->setStatusCode(201);
    }

    public function show(ItemMaintenance $maintenance)
    {
        return new MaintenanceResource($maintenance);
    }

    public function update(Request $request, ItemMaintenance $maintenance)
    {
        $maintenance->update($request->only([
            'maintenance_date', 'description', 'performed_by',
            'cost', 'status', 'notes',
        ]));

        return new MaintenanceResource($maintenance);
    }
}
