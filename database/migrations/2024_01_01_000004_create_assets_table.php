<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->year('year')->nullable();
            $table->string('condition')->default('GOOD'); // GOOD, FAIR, POOR, DAMAGED
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // UNIQUE, CONSUMABLE
            $table->integer('stock')->nullable(); // only for CONSUMABLE
            $table->string('status')->default('AVAILABLE'); // AVAILABLE, BORROWED, DAMAGED, LOST
            $table->text('description')->nullable();
            $table->string('asset_code')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('asset_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_images');
        Schema::dropIfExists('assets');
    }
};
