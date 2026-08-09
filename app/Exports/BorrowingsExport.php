<?php

namespace App\Exports;

use App\Services\ReportDataService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class BorrowingsExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
        private ReportDataService $reports = new ReportDataService,
    ) {}

    public function collection(): Collection
    {
        return $this->reports->borrowingRows($this->from, $this->to)->map(fn (array $row) => [
            $row['request_number'],
            $row['status'],
            $row['borrower'],
            $row['item_code'],
            $row['item_name'],
            $row['serial_number'],
            $row['quantity'],
            $row['borrow_date'],
            $row['expected_return_date'],
            $row['actual_return_date'],
            $row['requested_at'],
        ]);
    }

    public function headings(): array
    {
        return [
            'No. Request',
            'Status',
            'Peminjam',
            'Kode Item',
            'Nama Item',
            'Serial Number',
            'Qty',
            'Tanggal Pinjam',
            'Rencana Kembali',
            'Tanggal Kembali',
            'Diajukan Pada',
        ];
    }

    public function title(): string
    {
        return 'Riwayat Peminjaman';
    }
}
