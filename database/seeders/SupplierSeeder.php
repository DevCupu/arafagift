<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'CV Kurma Nusantara', 'phone' => '0812-3456-7801', 'email' => 'sales@kurmanusantara.id', 'address' => 'Jl. Raya Bogor KM 32, Jakarta Timur', 'note' => 'Kurma Ajwa & Sukkari'],
            ['name' => 'UD Sajadah Sejahtera', 'phone' => '0813-9876-5402', 'email' => 'cs@sajadahsejahtera.id', 'address' => 'Pasar Tanah Abang Blok B, Jakarta Pusat', 'note' => 'Sajadah travel & premium'],
            ['name' => 'Tasbih Kayu Abadi', 'phone' => '0857-1122-3344', 'email' => 'order@tasbihabadi.id', 'address' => 'Jl. Kaliurang KM 5, Yogyakarta', 'note' => 'Tasbih kayu zaitun & batu'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
