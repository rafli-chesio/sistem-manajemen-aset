<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a direct `role` column to users table.
 *
 * We keep Spatie for permission checking but add a simpler role column
 * for direct queries and RBAC checks without extra joins.
 *
 * Roles: ADMIN, KAJUR, VIEWER
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('VIEWER')->after('department');
        });

        // Seed role from existing Spatie roles if they exist
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                UPDATE users u
                JOIN model_has_roles mhr ON mhr.model_id = u.id AND mhr.model_type = 'App\\\\Models\\\\User'
                JOIN roles r ON r.id = mhr.role_id
                SET u.role = CASE
                    WHEN r.name IN ('super_admin', 'admin') THEN 'ADMIN'
                    WHEN r.name = 'kajur' THEN 'KAJUR'
                    ELSE 'VIEWER'
                END
            ");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
