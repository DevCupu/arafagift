<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('maintenance_title')->nullable()->after('closed_message');
            $table->string('maintenance_end_time', 50)->nullable()->after('maintenance_title');
            $table->text('maintenance_whitelist_ips')->nullable()->after('maintenance_end_time');
            $table->string('maintenance_secret', 100)->nullable()->after('maintenance_whitelist_ips');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_title',
                'maintenance_end_time',
                'maintenance_whitelist_ips',
                'maintenance_secret',
            ]);
        });
    }
};
