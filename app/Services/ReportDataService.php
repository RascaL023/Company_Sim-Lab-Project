<?php

namespace App\Services;

use App\Models\AssetDisposal;
use App\Models\BorrowingRequest;
use App\Models\Item;
use App\Models\ItemUnit;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportDataService
{
    public function inventoryRows(): Collection
    {
        return Item::query()
            ->with(['category', 'units'])
            ->orderBy('code')
            ->get()
            ->map(function (Item $item) {
                $units = $item->units;
                $isAlat = $item->category?->type === 'alat';

                return [
                    'code' => $item->code,
                    'name' => $item->name,
                    'category' => $item->category?->name,
                    'type' => $item->category?->type,
                    'unit' => $item->unit,
                    'stock_quantity' => (float) $item->stock_quantity,
                    'minimum_stock' => (float) $item->minimum_stock,
                    'units_total' => $isAlat ? $units->count() : null,
                    'units_baik' => $isAlat ? $units->where('condition', 'baik')->count() : null,
                    'units_rusak_ringan' => $isAlat ? $units->where('condition', 'rusak_ringan')->count() : null,
                    'units_rusak_berat' => $isAlat ? $units->where('condition', 'rusak_berat')->count() : null,
                    'units_hilang' => $isAlat ? $units->where('condition', 'hilang')->count() : null,
                    'units_dihapus' => $isAlat ? $units->where('condition', 'dihapus')->count() : null,
                ];
            });
    }

    public function borrowingRows(?string $from, ?string $to): Collection
    {
        $query = BorrowingRequest::query()
            ->with(['requester', 'items.item', 'items.itemUnit'])
            ->orderBy('requested_at');

        if ($from) {
            $query->whereDate('requested_at', '>=', Carbon::parse($from)->toDateString());
        }

        if ($to) {
            $query->whereDate('requested_at', '<=', Carbon::parse($to)->toDateString());
        }

        $rows = collect();

        foreach ($query->get() as $request) {
            if ($request->items->isEmpty()) {
                $rows->push([
                    'request_number' => $request->request_number,
                    'status' => $request->status,
                    'borrower' => $request->requester?->name,
                    'item_code' => null,
                    'item_name' => null,
                    'serial_number' => null,
                    'quantity' => null,
                    'borrow_date' => null,
                    'expected_return_date' => null,
                    'actual_return_date' => null,
                    'requested_at' => optional($request->requested_at)?->toDateTimeString(),
                ]);

                continue;
            }

            foreach ($request->items as $line) {
                $rows->push([
                    'request_number' => $request->request_number,
                    'status' => $request->status,
                    'borrower' => $request->requester?->name,
                    'item_code' => $line->item?->code,
                    'item_name' => $line->item?->name,
                    'serial_number' => $line->itemUnit?->serial_number,
                    'quantity' => $line->quantity,
                    'borrow_date' => optional($line->borrow_date)?->toDateString(),
                    'expected_return_date' => optional($line->expected_return_date)?->toDateString(),
                    'actual_return_date' => optional($line->actual_return_date)?->toDateString(),
                    'requested_at' => optional($request->requested_at)?->toDateTimeString(),
                ]);
            }
        }

        return $rows;
    }

    /**
     * @return array{damaged_units: Collection, approved_disposals: Collection}
     */
    public function damagedAssetData(): array
    {
        $damagedUnits = ItemUnit::query()
            ->with(['item', 'location'])
            ->whereIn('condition', ['rusak_ringan', 'rusak_berat', 'hilang'])
            ->orderBy('condition')
            ->orderBy('serial_number')
            ->get()
            ->map(fn (ItemUnit $unit) => [
                'source' => 'unit_condition',
                'item_code' => $unit->item?->code,
                'item_name' => $unit->item?->name,
                'serial_number' => $unit->serial_number,
                'asset_tag' => $unit->asset_tag,
                'condition' => $unit->condition,
                'location' => $unit->location?->name,
                'disposal_reason' => null,
                'disposal_status' => null,
                'reviewed_at' => null,
            ]);

        $approvedDisposals = AssetDisposal::query()
            ->with(['item', 'itemUnit.item'])
            ->where('status', 'disetujui')
            ->orderBy('reviewed_at')
            ->get()
            ->map(function (AssetDisposal $disposal) {
                $item = $disposal->item ?? $disposal->itemUnit?->item;

                return [
                    'source' => 'asset_disposal',
                    'item_code' => $item?->code,
                    'item_name' => $item?->name,
                    'serial_number' => $disposal->itemUnit?->serial_number,
                    'asset_tag' => $disposal->itemUnit?->asset_tag,
                    'condition' => $disposal->itemUnit?->condition,
                    'location' => null,
                    'disposal_reason' => $disposal->reason,
                    'disposal_status' => $disposal->status,
                    'reviewed_at' => optional($disposal->reviewed_at)?->toDateTimeString(),
                ];
            });

        return [
            'damaged_units' => $damagedUnits,
            'approved_disposals' => $approvedDisposals,
            'rows' => $damagedUnits->concat($approvedDisposals)->values(),
        ];
    }
}
