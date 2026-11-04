<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('pickup_enabled')->default(true)->after('shipping_note');
            $table->text('pickup_instructions')->nullable()->after('pickup_enabled');
            $table->unsignedBigInteger('min_order_amount')->default(0)->after('pickup_instructions');
            $table->string('qris_image_path')->nullable()->after('payment_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['pickup_enabled', 'pickup_instructions', 'min_order_amount', 'qris_image_path']);
        });
    }
};
