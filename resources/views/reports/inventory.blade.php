<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Inventaris</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        .meta { margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #555; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan Stok Inventaris</h1>
    <div class="meta">Dibuat: {{ $generatedAt->format('Y-m-d H:i') }}</div>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Kategori</th>
                <th>Tipe</th>
                <th>Stok</th>
                <th>Baik</th>
                <th>R. Ringan</th>
                <th>R. Berat</th>
                <th>Hilang</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['code'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['category'] }}</td>
                    <td>{{ $row['type'] }}</td>
                    <td class="num">{{ $row['stock_quantity'] }}</td>
                    <td class="num">{{ $row['units_baik'] ?? '-' }}</td>
                    <td class="num">{{ $row['units_rusak_ringan'] ?? '-' }}</td>
                    <td class="num">{{ $row['units_rusak_berat'] ?? '-' }}</td>
                    <td class="num">{{ $row['units_hilang'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
