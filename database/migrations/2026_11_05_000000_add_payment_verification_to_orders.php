<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Null = pesanan lama (sebelum fitur ini) atau COD: tidak pernah kedaluwarsa otomatis.
            $table->timestamp('payment_due_at')->nullable()->after('payment_status')->index();
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_due_at');
            $table->string('payment_reference', 100)->nullable()->after('payment_confirmed_at');
        });

        Schema::create('payment_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime', 100)->default('application/octet-stream');
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedBigInteger('amount')->nullable();
            $table->string('sender_name', 100)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 255)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });

        Schema::create('order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40)->index();
            $table->string('from_value', 60)->nullable();
            $table->string('to_value', 60)->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_events');
        Schema::dropIfExists('payment_confirmations');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_due_at']);
            $table->dropColumn(['payment_due_at', 'payment_confirmed_at', 'payment_reference']);
        });
    }
};
