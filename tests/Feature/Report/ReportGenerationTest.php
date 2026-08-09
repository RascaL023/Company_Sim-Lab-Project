<?php

namespace Tests\Feature\Report;

use App\Models\BorrowingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('test-token')->plainTextToken;
    }

    public function test_kepala_lab_can_download_inventory_pdf(): void
    {
        $kepala = User::factory()->kepalaLab()->create();

        $response = $this->withToken($this->tokenFor($kepala))
            ->get('/api/reports/inventory?format=pdf');

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_kepala_lab_can_download_inventory_excel(): void
    {
        $kepala = User::factory()->kepalaLab()->create();

        $response = $this->withToken($this->tokenFor($kepala))
            ->get('/api/reports/inventory?format=excel');

        $response->assertOk();
        $contentType = (string) $response->headers->get('content-type');
        $this->assertTrue(
            str_contains($contentType, 'spreadsheetml')
            || str_contains($contentType, 'officedocument')
            || str_contains($contentType, 'octet-stream'),
            "Unexpected content-type: {$contentType}"
        );
    }

    public function test_peminjam_cannot_access_any_report(): void
    {
        $peminjam = User::factory()->peminjam()->create();
        $token = $this->tokenFor($peminjam);

        foreach ([
            '/api/reports/inventory?format=pdf',
            '/api/reports/borrowings?format=excel',
            '/api/reports/damaged-assets?format=pdf',
        ] as $url) {
            Auth::forgetGuards();

            $this->withToken($token)
                ->get($url)
                ->assertForbidden();
        }
    }

    public function test_borrowings_report_respects_date_range_filter(): void
    {
        $kepala = User::factory()->kepalaLab()->create();
        $peminjam = User::factory()->peminjam()->create();

        BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
            'request_number' => 'BR-INSIDE-2026',
            'requested_at' => '2026-01-15 09:30:00',
        ]);

        BorrowingRequest::factory()->diajukan()->create([
            'requested_by' => $peminjam->id,
            'request_number' => 'BR-OUTSIDE-2025',
            'requested_at' => '2025-06-01 09:30:00',
        ]);

        $response = $this->withToken($this->tokenFor($kepala))
            ->get('/api/reports/borrowings?format=excel&from=2026-01-01&to=2026-01-31');

        $response->assertOk();
        $response->assertDownload('laporan-riwayat-peminjaman.xlsx');

        $path = $response->baseResponse->getFile()->getPathname();
        $rows = IOFactory::load($path)->getActiveSheet()->toArray();

        $requestNumbers = collect($rows)
            ->skip(1)
            ->pluck(0)
            ->filter()
            ->values();

        $this->assertTrue($requestNumbers->contains('BR-INSIDE-2026'));
        $this->assertFalse($requestNumbers->contains('BR-OUTSIDE-2025'));
        $this->assertCount(1, $requestNumbers);
    }

    public function test_borrowings_invalid_date_range_returns_validation_error(): void
    {
        $laboran = User::factory()->laboran()->create();

        $this->withToken($this->tokenFor($laboran))
            ->getJson('/api/reports/borrowings?format=pdf&from=2026-02-01&to=2026-01-01')
            ->assertStatus(422);
    }
}
