<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Null = belum pernah dikirim pengingat. Dipakai supaya pengingat
            // batas bayar tidak terkirim berulang setiap kali scheduler jalan.
            $table->timestamp('payment_reminder_sent_at')->nullable()->after('payment_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_reminder_sent_at');
        });
    }
};
