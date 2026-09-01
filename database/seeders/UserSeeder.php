<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['id' => 1, 'name' => 'Ratna Halim', 'email' => 'ratna.halim@mail.com', 'phone' => '0812-3344-5566', 'city' => 'Surabaya', 'tag' => 'Pelanggan tetap', 'is_admin' => false],
            ['id' => 2, 'name' => 'H. Zulkarnain', 'email' => 'zulkarnain@travelbarokah.id', 'phone' => '0813-9090-1122', 'city' => 'Solo', 'tag' => 'Travel / rombongan', 'is_admin' => false],
            ['id' => 3, 'name' => 'Dewi Anggraini', 'email' => 'dewi.ang@mail.com', 'phone' => '0811-2233-4455', 'city' => 'Makassar', 'tag' => 'Pelanggan tetap', 'is_admin' => false],
            ['id' => 4, 'name' => 'Fajar Nugroho', 'email' => 'fajar.n@mail.com', 'phone' => '0857-1212-3434', 'city' => 'Jakarta', 'tag' => 'Baru', 'is_admin' => false],
            ['id' => 5, 'name' => 'Siti Maryam', 'email' => 'siti.maryam@mail.com', 'phone' => '0821-5566-7788', 'city' => 'Bandung', 'tag' => 'Baru', 'is_admin' => false],
            ['id' => 6, 'name' => 'Abdul Rahman', 'email' => 'a.rahman@mail.com', 'phone' => '0878-4433-2211', 'city' => 'Parepare', 'tag' => 'Pelanggan tetap', 'is_admin' => false],
            ['id' => 7, 'name' => 'Admin Arafah', 'email' => 'admin@arafahgift.id', 'phone' => null, 'city' => null, 'tag' => null, 'is_admin' => true],
        ];

        foreach ($users as $user) {
            $record = User::updateOrCreate(['email' => $user['email']], [
                'id' => $user['id'],
                'name' => $user['name'],
                'password' => Hash::make('password'),
                'phone' => $user['phone'],
                'city' => $user['city'],
                'customer_tag' => $user['tag'],
                'email_verified_at' => now(),
            ]);

            // is_admin is intentionally not mass-assignable (see User::Fillable) — set it directly here.
            $record->forceFill(['is_admin' => $user['is_admin']])->save();
        }
    }
}
