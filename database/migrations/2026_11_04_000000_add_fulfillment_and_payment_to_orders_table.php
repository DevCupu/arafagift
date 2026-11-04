<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('fulfillment_type', 20)->default('delivery')->after('channel')->index();
            $table->string('payment_status', 20)->default('unpaid')->after('status')->index();
            $table->string('tracking_token', 64)->nullable()->unique()->after('order_number');
            $table->string('voucher_code', 40)->nullable()->after('fulfillment_type');
            $table->unsignedBigInteger('discount_total')->default(0)->after('subtotal');
        });

        DB::table('orders')->whereNull('tracking_token')->orderBy('id')->chunkById(500, function ($orders): void {
            foreach ($orders as $order) {
                DB::table('orders')->where('id', $order->id)->update([
                    'tracking_token' => Str::random(32),
                ]);
            }
        });

        DB::table('orders')
            ->whereIn('status', ['paid', 'processing', 'shipped', 'completed'])
            ->update(['payment_status' => 'paid']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn(['fulfillment_type', 'payment_status', 'tracking_token', 'voucher_code', 'discount_total']);
        });
    }
};
