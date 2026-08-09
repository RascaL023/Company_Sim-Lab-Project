<?php

namespace App\Exports;

use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventoryExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private ReportDataService $reports = new ReportDataService) {}

    public function collection(): Collection
    {
        return $this->reports->inventoryRows()->map(fn (array $row) => [
            $row['code'],
            $row['name'],
            $row['category'],
            $row['type'],
            $row['unit'],
            $row['stock_quantity'],
            $row['minimum_stock'],
            $row['units_total'],
            $row['units_baik'],
            $row['units_rusak_ringan'],
            $row['units_rusak_berat'],
            $row['units_hilang'],
            $row['units_dihapus'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Kode',
            'Nama',
            'Kategori',
            'Tipe',
            'Satuan',
            'Stok',
            'Stok Minimum',
            'Unit Total',
            'Baik',
            'Rusak Ringan',
            'Rusak Berat',
            'Hilang',
            'Dihapus',
        ];
    }

    public function title(): string
    {
        return 'Stok Inventaris';
    }
}
