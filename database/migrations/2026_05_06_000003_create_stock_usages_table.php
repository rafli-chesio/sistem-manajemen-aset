<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create stock_usages table for CONSUMABLE assets.
 *
 * CONSUMABLE assets use stock deduction (not borrowing/returning).
 * Each usage permanently reduces the stock count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('used_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('quantity_used')->default(1);
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->timestamps();

            $table->index(['asset_id', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_usages');
    }
};
