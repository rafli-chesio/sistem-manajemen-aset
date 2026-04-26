<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name'       => 'Super Admin',
                'password'   => Hash::make('password'),
                'nip'        => '000000000000000000',
                'department' => 'Wakil Kepala Sekolah',
            ]
        );

        $admin->assignRole('super_admin');

        // Sample Viewer account
        $viewer = User::firstOrCreate(
            ['email' => 'kepsek@sekolah.sch.id'],
            [
                'name'       => 'Kepala Sekolah',
                'password'   => Hash::make('password'),
                'nip'        => '196801011990011001',
                'department' => 'Kepala Sekolah',
            ]
        );
        $viewer->assignRole('viewer');

        // Sample Kajur account
        $kajur = User::firstOrCreate(
            ['email' => 'kajur.ti@sekolah.sch.id'],
            [
                'name'       => 'Kajur Teknik Informatika',
                'password'   => Hash::make('password'),
                'nip'        => '197503012005011002',
                'department' => 'Teknik Informatika',
            ]
        );
        $kajur->assignRole('kajur');
    }
}
