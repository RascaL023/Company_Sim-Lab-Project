<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Riwayat Peminjaman</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 3px 5px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Laporan Riwayat Peminjaman</h1>
    <div class="meta">
        Periode: {{ $from ?? 'awal' }} s/d {{ $to ?? 'akhir' }} |
        Dibuat: {{ $generatedAt->format('Y-m-d H:i') }}
    </div>
    <table>
        <thead>
            <tr>
                <th>No. Request</th>
                <th>Status</th>
                <th>Peminjam</th>
                <th>Item</th>
                <th>Serial</th>
                <th>Pinjam</th>
                <th>Rencana</th>
                <th>Kembali</th>
                <th>Diajukan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['request_number'] }}</td>
                    <td>{{ $row['status'] }}</td>
                    <td>{{ $row['borrower'] }}</td>
                    <td>{{ $row['item_code'] }} {{ $row['item_name'] }}</td>
                    <td>{{ $row['serial_number'] }}</td>
                    <td>{{ $row['borrow_date'] }}</td>
                    <td>{{ $row['expected_return_date'] }}</td>
                    <td>{{ $row['actual_return_date'] }}</td>
                    <td>{{ $row['requested_at'] }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data pada rentang tanggal.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
