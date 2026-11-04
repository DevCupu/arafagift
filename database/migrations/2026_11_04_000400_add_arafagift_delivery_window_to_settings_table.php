<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->time('arafagift_delivery_start')->default('13:00:00')->after('pickup_instructions');
            $table->time('arafagift_delivery_end')->default('16:00:00')->after('arafagift_delivery_start');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['arafagift_delivery_start', 'arafagift_delivery_end']);
        });
    }
};
