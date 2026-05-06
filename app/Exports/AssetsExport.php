<?php

namespace App\Exports;

use App\Models\Asset;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Asset::with(['category', 'location'])->withTrashed(false)->get();
    }

    public function headings(): array
    {
        return [
            'ID', 'Nama Barang', 'Kode', 'Merk', 'Tahun', 'Tipe',
            'Kondisi', 'Status', 'Stok', 'Jurusan',
            'Kategori', 'Lokasi', 'Tanggal Dibuat',
        ];
    }

    public function map($asset): array
    {
        $conditionMap = [
            'BAIK'         => 'Baik',
            'RUSAK_RINGAN' => 'Rusak Ringan',
            'RUSAK_BERAT'  => 'Rusak Berat',
        ];
        $statusMap = [
            'AVAILABLE'   => 'Tersedia',
            'BORROWED'    => 'Dipinjam',
            'MAINTENANCE' => 'Maintenance',
            'LOST'        => 'Hilang',
            'ARCHIVED'    => 'Diarsipkan',
        ];
        $typeMap = [
            'FIXED'       => 'Aset Tetap',
            'CONSUMABLE'  => 'Barang Habis Pakai',
        ];

        return [
            $asset->id,
            $asset->name,
            $asset->asset_code ?? '-',
            $asset->brand ?? '-',
            $asset->year ?? '-',
            $typeMap[$asset->type] ?? $asset->type,
            $conditionMap[$asset->condition] ?? $asset->condition,
            $statusMap[$asset->status] ?? $asset->status,
            $asset->stock ?? '-',
            $asset->department ?? '-',
            $asset->category?->name ?? '-',
            $asset->location?->name ?? '-',
            $asset->created_at->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
            ],
        ];
    }
}
