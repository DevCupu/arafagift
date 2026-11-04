<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'qris', 'name' => 'QRIS', 'note' => 'Semua e-wallet & mobile banking', 'is_active' => true, 'sort_order' => 10],
            ['code' => 'transfer', 'name' => 'Transfer bank', 'note' => 'BCA, Mandiri, BSI', 'is_active' => true, 'sort_order' => 20],
            ['code' => 'cod', 'name' => 'Bayar di Tempat (COD)', 'note' => 'Bayar saat pesanan diterima', 'is_active' => true, 'sort_order' => 30],
            // Belum ada payment gateway, jadi kartu kredit tidak ditampilkan.
            ['code' => 'card', 'name' => 'Kartu kredit / debit', 'note' => 'Visa, Mastercard', 'is_active' => false, 'sort_order' => 90],
        ];

        foreach ($methods as $method) {
            PaymentMethod::updateOrCreate(['code' => $method['code']], $method);
        }
    }
}
