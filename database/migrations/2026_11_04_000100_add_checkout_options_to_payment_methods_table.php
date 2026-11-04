<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('note');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
            $table->text('instructions')->nullable()->after('sort_order');
        });

        // Kartu kredit tidak dipakai: belum ada payment gateway.
        DB::table('payment_methods')->where('code', 'card')->update(['is_active' => false, 'sort_order' => 90]);

        DB::table('payment_methods')->where('code', 'qris')->update(['sort_order' => 10]);
        DB::table('payment_methods')->where('code', 'transfer')->update(['sort_order' => 20]);

        if (! DB::table('payment_methods')->where('code', 'cod')->exists()) {
            DB::table('payment_methods')->insert([
                'code' => 'cod',
                'name' => 'Bayar di Tempat (COD)',
                'note' => 'Bayar saat pesanan diterima',
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'sort_order', 'instructions']);
        });
    }
};
