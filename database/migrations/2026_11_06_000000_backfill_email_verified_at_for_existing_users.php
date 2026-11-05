<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Verifikasi email mulai diwajibkan untuk area akun. Akun yang sudah ada
     * (termasuk admin) ditandai terverifikasi supaya tidak tiba-tiba terkunci
     * dari halaman akun setelah deploy.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    /**
     * Sengaja tidak mengembalikan status verifikasi: membedakan akun lama dan
     * baru setelah migrasi ini berjalan tidak bisa dilakukan dengan aman.
     */
    public function down(): void
    {
        //
    }
};
