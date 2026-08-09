<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Aset Rusak / Hilang</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        h2 { font-size: 13px; margin-top: 16px; margin-bottom: 6px; }
        .meta { margin-bottom: 12px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #555; padding: 4px 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h1>Laporan Aset Rusak / Hilang</h1>
    <div class="meta">Dibuat: {{ $generatedAt->format('Y-m-d H:i') }}</div>

    <h2>Unit dengan kondisi rusak/hilang</h2>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Serial</th>
                <th>Kondisi</th>
                <th>Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($damagedUnits as $row)
                <tr>
                    <td>{{ $row['item_code'] }}</td>
                    <td>{{ $row['item_name'] }}</td>
                    <td>{{ $row['serial_number'] }}</td>
                    <td>{{ $row['condition'] }}</td>
                    <td>{{ $row['location'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada unit rusak/hilang.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Penghapusan aset disetujui</h2>
    <table>
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Serial</th>
                <th>Alasan</th>
                <th>Direview</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($approvedDisposals as $row)
                <tr>
                    <td>{{ $row['item_code'] }}</td>
                    <td>{{ $row['item_name'] }}</td>
                    <td>{{ $row['serial_number'] }}</td>
                    <td>{{ $row['disposal_reason'] }}</td>
                    <td>{{ $row['reviewed_at'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada disposal disetujui.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
