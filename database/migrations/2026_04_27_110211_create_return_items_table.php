<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();

            // FK ke tabel returns (satu return bisa punya banyak return_items)
            $table->foreignId('return_id')
                  ->constrained('returns')
                  ->cascadeOnDelete();

            // FK ke tabel borrow_items (melacak item spesifik yang dikembalikan)
            $table->foreignId('borrow_item_id')
                  ->constrained('borrow_items')
                  ->cascadeOnDelete();

            // Kondisi fisik aset SETELAH dikembalikan (per item)
            $table->string('condition_after')
                  ->comment('GOOD | FAIR | POOR | DAMAGED');

            // Catatan khusus untuk item ini (opsional)
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
