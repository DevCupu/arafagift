<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable()->change();
            $table->unsignedBigInteger('payment_method_id')->nullable()->change();
            $table->string('postal_code')->nullable()->change();
            $table->unsignedBigInteger('shipping_cost')->default(0)->change();
            $table->unsignedBigInteger('total')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable(false)->change();
            $table->unsignedBigInteger('payment_method_id')->nullable(false)->change();
            $table->string('postal_code')->nullable(false)->change();
            $table->unsignedBigInteger('shipping_cost')->change();
            $table->unsignedBigInteger('total')->change();
        });
    }
};
