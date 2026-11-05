<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Kapan alert stok menipis terakhir dikirim. Direset saat stok
            // diisi ulang di atas ambang, supaya periode menipis berikutnya
            // memicu email baru.
            $table->timestamp('low_stock_notified_at')->nullable()->after('low_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('low_stock_notified_at');
        });
    }
};
