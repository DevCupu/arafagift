<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Batas waktu pembayaran manual (jam). 24 jam adalah default yang wajar
            // untuk transfer/QRIS; admin bisa mengubahnya di panel Pengaturan.
            $table->unsignedSmallInteger('payment_window_hours')->default(24)->after('payment_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('payment_window_hours');
        });
    }
};
