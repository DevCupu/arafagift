<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['code' => 'transfer', 'name' => 'Transfer bank', 'note' => 'BCA, Mandiri, BSI'],
            ['code' => 'qris', 'name' => 'QRIS', 'note' => 'Semua e-wallet'],
            ['code' => 'card', 'name' => 'Kartu kredit / debit', 'note' => 'Visa, Mastercard'],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}
