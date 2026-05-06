<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // ADMIN — full access
        User::firstOrCreate(
            ['email' => 'admin@sekolah.sch.id'],
            [
                'name'       => 'Administrator',
                'password'   => Hash::make('password'),
                'nip'        => '000000000000000001',
                'department' => null,
                'role'       => User::ROLE_ADMIN,
            ]
        );

        // VIEWER — Kepala Sekolah, read-only
        User::firstOrCreate(
            ['email' => 'kepsek@sekolah.sch.id'],
            [
                'name'       => 'Kepala Sekolah',
                'password'   => Hash::make('password'),
                'nip'        => '196801011990011001',
                'department' => null,
                'role'       => User::ROLE_VIEWER,
            ]
        );

        // KAJUR — Kepala Jurusan Teknik Informatika
        User::firstOrCreate(
            ['email' => 'kajur.ti@sekolah.sch.id'],
            [
                'name'       => 'Kajur Teknik Informatika',
                'password'   => Hash::make('password'),
                'nip'        => '197503012005011002',
                'department' => json_encode(['Teknik Informatika']),
                'role'       => User::ROLE_KAJUR,
            ]
        );

        // KAJUR — Kepala Jurusan Akuntansi
        User::firstOrCreate(
            ['email' => 'kajur.akuntansi@sekolah.sch.id'],
            [
                'name'       => 'Kajur Akuntansi',
                'password'   => Hash::make('password'),
                'nip'        => '198002022010012003',
                'department' => json_encode(['Akuntansi']),
                'role'       => User::ROLE_KAJUR,
            ]
        );
    }
}
