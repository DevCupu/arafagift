<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('discount_type', 20)->default('percent')->after('period');
            $table->unsignedInteger('discount_value')->default(0)->after('discount_type');
            $table->unsignedBigInteger('min_purchase')->default(0)->after('discount_value');
            $table->unsignedBigInteger('max_discount')->nullable()->after('min_purchase');
            $table->timestamp('starts_at')->nullable()->after('max_discount');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->unsignedInteger('usage_limit')->nullable()->after('ends_at');
            $table->unsignedInteger('used_count')->default(0)->after('usage_limit');
            $table->string('applies_to', 20)->default('all')->after('used_count');
            $table->string('applies_value')->nullable()->after('applies_to');
            $table->boolean('is_active')->default(true)->after('applies_value');
        });

        // Promo lama tetap tampil, tapi belum punya nilai diskon sampai diedit admin.
        DB::table('promotions')->where('status', 'ended')->update(['is_active' => false]);
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn([
                'discount_type', 'discount_value', 'min_purchase', 'max_discount',
                'starts_at', 'ends_at', 'usage_limit', 'used_count', 'applies_to', 'applies_value', 'is_active',
            ]);
        });
    }
};
