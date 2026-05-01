<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Add database-level CHECK constraints as second line of defense.
 *
 * These constraints enforce invariants that application logic already maintains,
 * but without DB enforcement, a bug or direct DB access could silently corrupt data.
 *
 * Requires MySQL 8.0.16+ for CHECK constraint enforcement.
 * On older MySQL, these are parsed but NOT enforced (safe to run, just ineffective).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Verify existing data before adding constraints
        $violations = DB::select("
            SELECT COUNT(*) as cnt FROM assets
            WHERE (type = 'CONSUMABLE' AND stock IS NULL)
               OR (type = 'CONSUMABLE' AND stock < 0)
        ");

        if ($violations[0]->cnt > 0) {
            // Fix data before adding constraint — set NULL stock to 0 for consumables
            DB::statement("UPDATE assets SET stock = 0 WHERE type = 'CONSUMABLE' AND stock IS NULL");
        }

        if (DB::getDriverName() !== 'sqlite') {
            // 1. stock cannot be negative
            DB::statement("
                ALTER TABLE assets
                ADD CONSTRAINT chk_stock_non_negative
                CHECK (stock IS NULL OR stock >= 0)
            ");

            // 2. return_date must not be before borrow_date
            DB::statement("
                ALTER TABLE borrow_requests
                ADD CONSTRAINT chk_valid_date_range
                CHECK (return_date >= borrow_date)
            ");

            // 3. Asset type consistency: UNIQUE → stock IS NULL, CONSUMABLE → stock IS NOT NULL
            DB::statement("
                ALTER TABLE assets
                ADD CONSTRAINT chk_asset_type_stock_consistency
                CHECK (
                    (type = 'UNIQUE'     AND stock IS NULL)
                    OR
                    (type = 'CONSUMABLE' AND stock IS NOT NULL AND stock >= 0)
                )
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS chk_stock_non_negative');
            DB::statement('ALTER TABLE borrow_requests DROP CONSTRAINT IF EXISTS chk_valid_date_range');
            DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS chk_asset_type_stock_consistency');
        }
    }
};
