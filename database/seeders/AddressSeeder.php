<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;

class AddressSeeder extends Seeder
{
    public function run(): void
    {
        $addresses = [
            ['user_id' => 1, 'label' => 'Rumah', 'recipient_name' => 'Ratna Halim', 'phone' => '0812-3344-5566', 'address_text' => 'Jl. Dharmahusada Indah 12, Surabaya, Jawa Timur 60285', 'is_primary' => true],
            ['user_id' => 1, 'label' => 'Rumah Ibu', 'recipient_name' => 'Hj. Aminah', 'phone' => '0813-7788-9900', 'address_text' => 'Jl. Melati 4 RT 02/RW 05, Kediri, Jawa Timur 64114', 'is_primary' => false],
        ];

        foreach ($addresses as $address) {
            Address::create($address);
        }
    }
}
