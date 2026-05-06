<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Manajemen Aset Sekolah</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; background: #fff; }

        .header { background: #4f46e5; color: white; padding: 20px 28px; display: flex; justify-content: space-between; align-items: flex-start; }
        .header h1 { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .header p { font-size: 10px; opacity: 0.8; }
        .header-right { text-align: right; font-size: 10px; opacity: 0.9; }

        .content { padding: 24px 28px; }

        /* Metric Cards */
        .metrics { display: flex; gap: 12px; margin-bottom: 24px; }
        .metric-card { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 14px; }
        .metric-card .label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; font-weight: 600; margin-bottom: 4px; }
        .metric-card .value { font-size: 22px; font-weight: bold; color: #1e293b; }
        .metric-card.green  .value { color: #16a34a; }
        .metric-card.blue   .value { color: #2563eb; }
        .metric-card.amber  .value { color: #d97706; }
        .metric-card.red    .value { color: #dc2626; }

        /* Section */
        .section { margin-bottom: 22px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e293b; border-bottom: 2px solid #4f46e5; padding-bottom: 5px; margin-bottom: 12px; }

        /* Table */
        table { width: 100%; border-collapse: collapse; }
        th { background: #f1f5f9; text-transform: uppercase; font-size: 9px; letter-spacing: 0.5px; color: #64748b; font-weight: 600; padding: 8px 10px; text-align: left; }
        td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; font-size: 10px; color: #374151; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        /* Badge */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; font-weight: 600; }
        .badge-green  { background: #dcfce7; color: #16a34a; }
        .badge-amber  { background: #fef3c7; color: #d97706; }
        .badge-red    { background: #fee2e2; color: #dc2626; }
        .badge-blue   { background: #dbeafe; color: #2563eb; }
        .badge-slate  { background: #f1f5f9; color: #64748b; }

        /* Two column layout */
        .two-col { display: flex; gap: 20px; }
        .two-col > div { flex: 1; }

        /* Simple bar chart */
        .bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .bar-label { width: 100px; font-size: 9px; color: #475569; text-align: right; flex-shrink: 0; }
        .bar-track { flex: 1; background: #f1f5f9; border-radius: 4px; height: 14px; overflow: hidden; }
        .bar-fill { height: 100%; background: #4f46e5; border-radius: 4px; }
        .bar-val { font-size: 9px; font-weight: 600; color: #374151; width: 28px; text-align: right; flex-shrink: 0; }

        .footer { border-top: 1px solid #e2e8f0; padding: 10px 28px; font-size: 9px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div>
        <h1>📊 Laporan Manajemen Aset Sekolah</h1>
        <p>Sistem Informasi Inventaris &amp; Peminjaman Aset</p>
    </div>
    <div class="header-right">
        <div>Digenerate: {{ $generated_at }}</div>
        <div>Oleh: {{ $generated_by }}</div>
    </div>
</div>

<div class="content">

    <!-- Metric Cards -->
    <div class="metrics">
        <div class="metric-card">
            <div class="label">Total Aset</div>
            <div class="value">{{ $metrics['total_assets'] }}</div>
        </div>
        <div class="metric-card green">
            <div class="label">Tersedia</div>
            <div class="value">{{ $metrics['available_assets'] }}</div>
        </div>
        <div class="metric-card blue">
            <div class="label">Dipinjam</div>
            <div class="value">{{ $metrics['borrowed_assets'] }}</div>
        </div>
        <div class="metric-card amber">
            <div class="label">Maintenance</div>
            <div class="value">{{ $metrics['maintenance_assets'] }}</div>
        </div>
        <div class="metric-card red">
            <div class="label">Rusak</div>
            <div class="value">{{ $metrics['damaged_assets'] }}</div>
        </div>
        <div class="metric-card">
            <div class="label">Total Transaksi</div>
            <div class="value">{{ $metrics['total_transactions'] }}</div>
        </div>
    </div>

    <!-- Two column: Category chart + Condition summary -->
    <div class="two-col">
        <div>
            <div class="section">
                <div class="section-title">Aset per Kategori</div>
                @php $maxCat = $assetsByCategory->max('count') ?: 1; @endphp
                @foreach($assetsByCategory as $cat)
                <div class="bar-row">
                    <div class="bar-label">{{ Str::limit($cat['name'], 14) }}</div>
                    <div class="bar-track">
                        <div class="bar-fill" style="width:{{ round(($cat['count'] / $maxCat) * 100) }}%"></div>
                    </div>
                    <div class="bar-val">{{ $cat['count'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        <div>
            <div class="section">
                <div class="section-title">Kondisi Aset</div>
                <table>
                    <thead>
                        <tr><th>Kondisi</th><th style="text-align:right">Jumlah</th></tr>
                    </thead>
                    <tbody>
                        @foreach($assetsByCondition as $condition => $count)
                        <tr>
                            <td>
                                @if($condition === 'BAIK')
                                    <span class="badge badge-green">Baik</span>
                                @elseif($condition === 'RUSAK_RINGAN')
                                    <span class="badge badge-amber">Rusak Ringan</span>
                                @else
                                    <span class="badge badge-red">Rusak Berat</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:bold">{{ $count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="section">
                <div class="section-title">Status Transaksi</div>
                <table>
                    <thead>
                        <tr><th>Status</th><th style="text-align:right">Jumlah</th></tr>
                    </thead>
                    <tbody>
                        @foreach($borrowsByStatus as $status => $count)
                        <tr>
                            <td>
                                @if($status === 'APPROVED') <span class="badge badge-green">Disetujui</span>
                                @elseif($status === 'PENDING') <span class="badge badge-amber">Menunggu</span>
                                @elseif($status === 'REJECTED') <span class="badge badge-red">Ditolak</span>
                                @elseif($status === 'RETURNED') <span class="badge badge-blue">Dikembalikan</span>
                                @else <span class="badge badge-slate">{{ $status }}</span>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:bold">{{ $count }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Top Aset Paling Sering Dipinjam -->
    @if(count($topAssets) > 0)
    <div class="section">
        <div class="section-title">10 Aset Paling Sering Dipinjam</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Barang</th>
                    <th>Kode</th>
                    <th style="text-align:right">Frekuensi Pinjam</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topAssets as $i => $asset)
                <tr>
                    <td style="color:#94a3b8">{{ $i + 1 }}</td>
                    <td style="font-weight:600">{{ $asset['name'] }}</td>
                    <td style="font-family:monospace; color:#64748b">{{ $asset['code'] ?? '-' }}</td>
                    <td style="text-align:right">
                        <span class="badge badge-blue">{{ $asset['count'] }}x</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Aset Rusak -->
    @if(count($damagedAssets) > 0)
    <div class="section">
        <div class="section-title">Daftar Aset Rusak</div>
        <table>
            <thead>
                <tr>
                    <th>Nama Barang</th>
                    <th>Kode</th>
                    <th>Kondisi</th>
                    <th>Kategori</th>
                    <th>Lokasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($damagedAssets as $asset)
                <tr>
                    <td style="font-weight:600">{{ $asset['name'] }}</td>
                    <td style="font-family:monospace; color:#64748b">{{ $asset['code'] ?? '-' }}</td>
                    <td>
                        @if($asset['condition'] === 'RUSAK_RINGAN')
                            <span class="badge badge-amber">Rusak Ringan</span>
                        @else
                            <span class="badge badge-red">Rusak Berat</span>
                        @endif
                    </td>
                    <td>{{ $asset['category'] ?? '-' }}</td>
                    <td>{{ $asset['location'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>

<div class="footer">
    Dokumen ini digenerate secara otomatis oleh Sistem Manajemen Aset Sekolah &bull; {{ $generated_at }}
</div>

</body>
</html>
