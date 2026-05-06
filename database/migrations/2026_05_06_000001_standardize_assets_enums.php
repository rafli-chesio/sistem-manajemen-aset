<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Standardize enums on the assets table.
 *
 * Condition: GOOD/FAIR/POOR/DAMAGED → BAIK / RUSAK_RINGAN / RUSAK_BERAT
 * Status:    add MAINTENANCE, ARCHIVED; drop DAMAGED
 * Type:      UNIQUE → FIXED
 */
return new class extends Migration
{
    public function up(): void
    {
        // Temporarily disable check constraints to allow data migration
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS chk_asset_type_stock_consistency');
            DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS chk_stock_non_negative');
        }
        // --- 1. Migrate data values BEFORE changing column definitions ---

        // Condition mapping
        DB::table('assets')->where('condition', 'GOOD')->update(['condition' => 'BAIK']);
        DB::table('assets')->where('condition', 'FAIR')->update(['condition' => 'RUSAK_RINGAN']);
        DB::table('assets')->whereIn('condition', ['POOR', 'DAMAGED'])->update(['condition' => 'RUSAK_BERAT']);

        // Status mapping (DAMAGED status → MAINTENANCE)
        DB::table('assets')->where('status', 'DAMAGED')->update(['status' => 'MAINTENANCE']);

        // Type mapping
        DB::table('assets')->where('type', 'UNIQUE')->update(['type' => 'FIXED']);

        // --- 2. Alter columns (MySQL supports modifying enum inline) ---
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE assets MODIFY COLUMN `condition` ENUM('BAIK','RUSAK_RINGAN','RUSAK_BERAT') NOT NULL DEFAULT 'BAIK'");
            DB::statement("ALTER TABLE assets MODIFY COLUMN `status` ENUM('AVAILABLE','BORROWED','MAINTENANCE','LOST','ARCHIVED') NOT NULL DEFAULT 'AVAILABLE'");
            DB::statement("ALTER TABLE assets MODIFY COLUMN `type` ENUM('FIXED','CONSUMABLE') NOT NULL");
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE assets MODIFY COLUMN `condition` ENUM('GOOD','FAIR','POOR','DAMAGED') NOT NULL DEFAULT 'GOOD'");
            DB::statement("ALTER TABLE assets MODIFY COLUMN `status` ENUM('AVAILABLE','BORROWED','DAMAGED','LOST') NOT NULL DEFAULT 'AVAILABLE'");
            DB::statement("ALTER TABLE assets MODIFY COLUMN `type` ENUM('UNIQUE','CONSUMABLE') NOT NULL");
        }

        DB::table('assets')->where('condition', 'BAIK')->update(['condition' => 'GOOD']);
        DB::table('assets')->where('condition', 'RUSAK_RINGAN')->update(['condition' => 'FAIR']);
        DB::table('assets')->where('condition', 'RUSAK_BERAT')->update(['condition' => 'DAMAGED']);
        DB::table('assets')->where('status', 'MAINTENANCE')->update(['status' => 'DAMAGED']);
        DB::table('assets')->where('status', 'ARCHIVED')->update(['status' => 'AVAILABLE']);
        DB::table('assets')->where('type', 'FIXED')->update(['type' => 'UNIQUE']);
    }
};
