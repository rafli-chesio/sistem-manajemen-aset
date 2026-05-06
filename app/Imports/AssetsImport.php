<?php

namespace App\Imports;

use App\Models\Asset;
use App\Models\Category;
use App\Models\Location;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AssetsImport implements ToCollection, WithHeadingRow
{
    public array $errors   = [];
    public int   $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // header is row 1

            try {
                // Resolve Category
                $category = null;
                if (!empty($row['kategori'])) {
                    $category = Category::firstOrCreate(['name' => trim($row['kategori'])]);
                }

                // Resolve Location
                $location = null;
                if (!empty($row['lokasi'])) {
                    $location = Location::firstOrCreate(['name' => trim($row['lokasi'])]);
                }

                // Normalize type
                $typeRaw = strtoupper(trim($row['tipe'] ?? 'FIXED'));
                $type    = in_array($typeRaw, ['FIXED', 'CONSUMABLE']) ? $typeRaw : 'FIXED';

                // Normalize condition
                $condMap = [
                    'BAIK'         => 'BAIK',
                    'BAIK'        => 'BAIK',
                    'RUSAK RINGAN' => 'RUSAK_RINGAN',
                    'RUSAK_RINGAN' => 'RUSAK_RINGAN',
                    'RUSAK BERAT'  => 'RUSAK_BERAT',
                    'RUSAK_BERAT'  => 'RUSAK_BERAT',
                ];
                $condRaw   = strtoupper(trim($row['kondisi'] ?? 'BAIK'));
                $condition = $condMap[$condRaw] ?? 'BAIK';

                Asset::create([
                    'name'        => trim($row['nama_barang']),
                    'brand'       => trim($row['merk'] ?? ''),
                    'year'        => !empty($row['tahun']) ? (int) $row['tahun'] : null,
                    'asset_code'  => !empty($row['kode_barang']) ? trim($row['kode_barang']) : null,
                    'type'        => $type,
                    'condition'   => $condition,
                    'status'      => 'AVAILABLE',
                    'stock'       => $type === 'CONSUMABLE' ? (int) ($row['stok'] ?? 0) : null,
                    'description' => trim($row['keterangan'] ?? ''),
                    'department'  => !empty($row['jurusan']) ? trim($row['jurusan']) : null,
                    'category_id' => $category?->id,
                    'location_id' => $location?->id,
                ]);

                $this->imported++;
            } catch (\Exception $e) {
                $this->errors[] = "Baris {$rowNum} ('{$row['nama_barang']}'): " . $e->getMessage();
            }
        }
    }
}
