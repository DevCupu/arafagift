<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pesanan manual yang tidak dibayar sampai batas waktu otomatis dibatalkan.
// Kalau hosting tidak menjalankan cron, OrderLifecycle::sweepIfDue() tetap
// menjalankan pembersihan yang sama saat halaman pesanan dibuka.
Schedule::command('orders:expire-unpaid')
    ->everyTenMinutes()
    ->withoutOverlapping();

// Pengingat sekali untuk pesanan yang batas bayarnya kurang dari 3 jam lagi.
Schedule::command('orders:remind-payment')
    ->everyTenMinutes()
    ->withoutOverlapping();

// Ringkasan produk stok menipis untuk admin, tiap pagi waktu Makassar.
Schedule::command('inventory:low-stock-alert')
    ->timezone('Asia/Makassar')
    ->dailyAt('08:00');
