<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['id' => 1, 'name' => 'Ratna Halim', 'email' => 'ratna.halim@mail.com', 'phone' => '0812-3344-5566', 'city' => 'Surabaya', 'tag' => 'Pelanggan tetap'],
            ['id' => 2, 'name' => 'H. Zulkarnain', 'email' => 'zulkarnain@travelbarokah.id', 'phone' => '0813-9090-1122', 'city' => 'Solo', 'tag' => 'Travel / rombongan'],
            ['id' => 3, 'name' => 'Dewi Anggraini', 'email' => 'dewi.ang@mail.com', 'phone' => '0811-2233-4455', 'city' => 'Makassar', 'tag' => 'Pelanggan tetap'],
            ['id' => 4, 'name' => 'Fajar Nugroho', 'email' => 'fajar.n@mail.com', 'phone' => '0857-1212-3434', 'city' => 'Jakarta', 'tag' => 'Baru'],
            ['id' => 5, 'name' => 'Siti Maryam', 'email' => 'siti.maryam@mail.com', 'phone' => '0821-5566-7788', 'city' => 'Bandung', 'tag' => 'Baru'],
            ['id' => 6, 'name' => 'Abdul Rahman', 'email' => 'a.rahman@mail.com', 'phone' => '0878-4433-2211', 'city' => 'Parepare', 'tag' => 'Pelanggan tetap'],
        ];

        foreach ($customers as $customer) {
            User::create([
                'id' => $customer['id'],
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make('password'),
                'phone' => $customer['phone'],
                'city' => $customer['city'],
                'customer_tag' => $customer['tag'],
                'email_verified_at' => now(),
            ]);
        }
    }
}
