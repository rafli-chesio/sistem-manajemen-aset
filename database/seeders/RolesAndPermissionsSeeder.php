<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permissions ────────────────────────────────────────────────
        $permissions = [
            // Users
            'user.view', 'user.create', 'user.edit', 'user.delete',

            // Assets
            'asset.view', 'asset.create', 'asset.edit', 'asset.delete',

            // Categories & Locations
            'category.view', 'category.create', 'category.edit', 'category.delete',
            'location.view', 'location.create', 'location.edit', 'location.delete',

            // Borrow
            'borrow.view',        // see all requests
            'borrow.view_own',    // see own requests
            'borrow.create',      // submit a request
            'borrow.approve',     // approve / reject
            'borrow.delete',

            // Returns
            'return.create',
            'return.view',

            // Reports & Audit
            'report.view',
            'audit.view',

            // Notifications
            'notification.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Roles ──────────────────────────────────────────────────────

        /** Super Admin — full access KECUALI borrow.create & return.create (hak kajur) */
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions([
            // Users
            'user.view', 'user.create', 'user.edit', 'user.delete',
            // Assets
            'asset.view', 'asset.create', 'asset.edit', 'asset.delete',
            // Categories & Locations
            'category.view', 'category.create', 'category.edit', 'category.delete',
            'location.view', 'location.create', 'location.edit', 'location.delete',
            // Borrow — admin hanya approve/reject & lihat semua, tidak bisa buat/kembalikan
            'borrow.view', 'borrow.approve', 'borrow.delete',
            // Returns — admin hanya lihat
            'return.view',
            // Reports & Audit
            'report.view', 'audit.view',
            // Notifications
            'notification.view',
        ]);

        /** Viewer (Kepala Sekolah, Bendahara) — read-only */
        $viewer = Role::firstOrCreate(['name' => 'viewer', 'guard_name' => 'web']);
        $viewer->syncPermissions([
            'asset.view',
            'borrow.view',
            'return.view',
            'report.view',
            'category.view',
            'location.view',
            'notification.view',
        ]);

        /** Kajur — hanya bisa lihat aset, submit & proses peminjaman */
        $kajur = Role::firstOrCreate(['name' => 'kajur', 'guard_name' => 'web']);
        $kajur->syncPermissions([
            'asset.view',
            'category.view',
            'location.view',
            'borrow.view_own', 'borrow.create',
            'return.create', 'return.view',
            'notification.view',
        ]);
    }
}
