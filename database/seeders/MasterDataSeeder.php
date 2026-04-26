<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Location;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Elektronik',    'description' => 'Peralatan elektronik seperti laptop, proyektor, dll.'],
            ['name' => 'Furnitur',      'description' => 'Meja, kursi, lemari, dan perabot lainnya'],
            ['name' => 'Alat Tulis',    'description' => 'Pena, spidol, kertas, dan perlengkapan tulis'],
            ['name' => 'Olahraga',      'description' => 'Peralatan olahraga dan kebugaran'],
            ['name' => 'Kebersihan',    'description' => 'Perlengkapan kebersihan dan sanitasi'],
            ['name' => 'Laboratorium',  'description' => 'Alat dan bahan laboratorium'],
            ['name' => 'Perpustakaan',  'description' => 'Buku, rak, dan perlengkapan perpustakaan'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        $locations = [
            ['name' => 'Ruang Kelas 1A',    'description' => 'Lantai 1, Gedung A'],
            ['name' => 'Ruang Kelas 1B',    'description' => 'Lantai 1, Gedung A'],
            ['name' => 'Laboratorium TI',   'description' => 'Lantai 2, Gedung B'],
            ['name' => 'Laboratorium IPA',  'description' => 'Lantai 1, Gedung C'],
            ['name' => 'Perpustakaan',      'description' => 'Lantai 1, Gedung A'],
            ['name' => 'Ruang Guru',        'description' => 'Lantai 1, Gedung A'],
            ['name' => 'Ruang Kepala',      'description' => 'Lantai 1, Gedung A'],
            ['name' => 'Gudang Umum',       'description' => 'Gedung Belakang'],
            ['name' => 'Lapangan',          'description' => 'Area luar sekolah'],
        ];

        foreach ($locations as $loc) {
            Location::firstOrCreate(['name' => $loc['name']], $loc);
        }
    }
}
