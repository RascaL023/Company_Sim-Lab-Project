<?php

namespace App\Http\Controllers\Api;

use App\Exports\BorrowingsExport;
use App\Exports\DamagedAssetsExport;
use App\Exports\InventoryExport;
use App\Http\Controllers\Controller;
use App\Services\ReportDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct(private ReportDataService $reports) {}

    public function inventory(Request $request): mixed
    {
        Gate::authorize('viewReports');

        $format = $this->validatedFormat($request);

        if ($format === 'excel') {
            return Excel::download(new InventoryExport($this->reports), 'laporan-stok-inventaris.xlsx');
        }

        $pdf = Pdf::loadView('reports.inventory', [
            'rows' => $this->reports->inventoryRows(),
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-stok-inventaris.pdf');
    }

    public function borrowings(Request $request): mixed
    {
        Gate::authorize('viewReports');

        $validated = $request->validate([
            'format' => 'required|in:pdf,excel',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $from = $validated['from'] ?? null;
        $to = $validated['to'] ?? null;
        $rows = $this->reports->borrowingRows($from, $to);

        if ($validated['format'] === 'excel') {
            return Excel::download(
                new BorrowingsExport($from, $to, $this->reports),
                'laporan-riwayat-peminjaman.xlsx'
            );
        }

        $pdf = Pdf::loadView('reports.borrowings', [
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-riwayat-peminjaman.pdf');
    }

    public function damagedAssets(Request $request): mixed
    {
        Gate::authorize('viewReports');

        $format = $this->validatedFormat($request);
        $data = $this->reports->damagedAssetData();

        if ($format === 'excel') {
            return Excel::download(new DamagedAssetsExport($this->reports), 'laporan-aset-rusak-hilang.xlsx');
        }

        $pdf = Pdf::loadView('reports.damaged-assets', [
            'damagedUnits' => $data['damaged_units'],
            'approvedDisposals' => $data['approved_disposals'],
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-aset-rusak-hilang.pdf');
    }

    private function validatedFormat(Request $request): string
    {
        return $request->validate([
            'format' => 'required|in:pdf,excel',
        ])['format'];
    }
}
