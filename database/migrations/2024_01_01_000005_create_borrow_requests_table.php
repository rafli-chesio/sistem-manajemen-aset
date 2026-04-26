<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('borrow_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('PENDING'); // PENDING, APPROVED, REJECTED, OVERDUE, RETURNED
            $table->date('borrow_date');
            $table->date('return_date');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('borrow_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained('borrow_requests')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('condition_before')->nullable(); // asset condition at borrow time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('borrow_items');
        Schema::dropIfExists('borrow_requests');
    }
};
