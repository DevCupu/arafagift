<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kategori ini belum ada di database produksi meski tautannya sudah tampil.
        // Jangan mengubah kategori atau produk yang sudah dikelola lewat admin.
        if (DB::table('categories')->where('slug', 'gift-set')->exists()) {
            return;
        }

        DB::table('categories')->insert([
            'name' => 'Gift Set',
            'slug' => 'gift-set',
            'art' => 'giftset',
            'tagline' => 'Siap diberikan',
            'product_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Kategori dapat memperoleh produk setelah dibuat; rollback kode tidak
        // boleh menghapus kategori atau relasi produk yang diatur lewat admin.
    }
};
