<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->integer('delta');
            $table->unsignedInteger('balance_before');
            $table->unsignedInteger('balance_after');
            $table->string('note')->nullable();
            $table->string('order_number')->nullable();
            $table->string('document_number')->unique();
            $table->timestamps();

            $table->index(['product_id', 'created_at']);
            $table->unique(['product_id', 'order_number', 'type'], 'stock_movements_order_idempotency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
