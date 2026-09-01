<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->index('status');
            $table->index(['featured', 'featured_order']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['featured', 'featured_order']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
        });
    }
};
