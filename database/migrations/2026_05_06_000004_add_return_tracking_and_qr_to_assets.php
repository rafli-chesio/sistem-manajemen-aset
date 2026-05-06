<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance borrow_items table with per-item return tracking.
 * Also add QR code field to assets table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add return tracking to borrow_items
        Schema::table('borrow_items', function (Blueprint $table) {
            $table->string('condition_after')->nullable()->after('condition_before');
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete()->after('condition_after');
            $table->timestamp('returned_at')->nullable()->after('returned_by');
            $table->text('return_notes')->nullable()->after('returned_at');
        });

        // Add QR code and condition_before_label to assets
        Schema::table('assets', function (Blueprint $table) {
            $table->string('qr_code')->nullable()->unique()->after('asset_code');
        });
    }

    public function down(): void
    {
        Schema::table('borrow_items', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->dropColumn(['condition_after', 'returned_by', 'returned_at', 'return_notes']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['qr_code']);
        });
    }
};
