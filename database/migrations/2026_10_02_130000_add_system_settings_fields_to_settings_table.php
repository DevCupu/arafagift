<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Jam operasional & identitas tambahan
            $table->string('business_hours')->nullable()->after('address');
            $table->string('whatsapp_secondary', 30)->nullable()->after('whatsapp');
            $table->string('instagram_url')->nullable()->after('whatsapp_secondary');
            $table->string('tiktok_url')->nullable()->after('instagram_url');
            $table->text('maps_url')->nullable()->after('tiktok_url');

            // Aturan logistik & pengiriman tambahan
            $table->string('handling_time')->nullable()->after('free_shipping_cities');
            $table->text('shipping_note')->nullable()->after('handling_time');

            // Operasional & status toko
            $table->string('store_status', 20)->default('open')->after('shipping_note');
            $table->text('closed_message')->nullable()->after('store_status');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('closed_message');

            // Rekening pembayaran manual
            $table->string('bank_name', 50)->nullable()->after('low_stock_threshold');
            $table->string('bank_account_number', 50)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account_number');
            $table->text('payment_instructions')->nullable()->after('bank_account_name');

            // SEO & Analitik global
            $table->string('meta_title')->nullable()->after('payment_instructions');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('google_analytics_id', 50)->nullable()->after('meta_description');
            $table->string('facebook_pixel_id', 50)->nullable()->after('google_analytics_id');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'business_hours',
                'whatsapp_secondary',
                'instagram_url',
                'tiktok_url',
                'maps_url',
                'handling_time',
                'shipping_note',
                'store_status',
                'closed_message',
                'low_stock_threshold',
                'bank_name',
                'bank_account_number',
                'bank_account_name',
                'payment_instructions',
                'meta_title',
                'meta_description',
                'google_analytics_id',
                'facebook_pixel_id',
            ]);
        });
    }
};
