<?php

namespace App\Exports;

use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DamagedAssetsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private ReportDataService $reports = new ReportDataService) {}

    public function collection(): Collection
    {
        return $this->reports->damagedAssetData()['rows']->map(fn (array $row) => [
            $row['source'],
            $row['item_code'],
            $row['item_name'],
            $row['serial_number'],
            $row['asset_tag'],
            $row['condition'],
            $row['location'],
            $row['disposal_reason'],
            $row['disposal_status'],
            $row['reviewed_at'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Sumber',
            'Kode Item',
            'Nama Item',
            'Serial Number',
            'Asset Tag',
            'Kondisi',
            'Lokasi',
            'Alasan Disposal',
            'Status Disposal',
            'Direview Pada',
        ];
    }

    public function title(): string
    {
        return 'Aset Rusak Hilang';
    }
}
