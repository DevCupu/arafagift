<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kurma', 'slug' => 'kurma', 'art' => 'kurma', 'image' => '/images/catalog/kurma.jpg', 'tagline' => 'Ajwa, Sukkari, Medjool', 'product_count' => 18],
            ['name' => 'Sajadah', 'slug' => 'sajadah', 'art' => 'sajadah', 'image' => '/images/catalog/sajadah.jpg', 'tagline' => 'Travel & premium', 'product_count' => 12],
            ['name' => 'Tasbih', 'slug' => 'tasbih', 'art' => 'tasbih', 'image' => '/images/catalog/tasbih.jpg', 'tagline' => 'Kayu zaitun & batu', 'product_count' => 9],
            ['name' => 'Gift Set', 'slug' => 'gift-set', 'art' => 'giftset', 'image' => '/images/catalog/bhukur.jpg', 'tagline' => 'Siap diberikan', 'product_count' => 7],
            ['name' => 'Oleh-oleh', 'slug' => 'oleh-oleh', 'art' => 'madu', 'image' => '/images/catalog/kalung.jpg', 'tagline' => 'Madu, kacang, parfum', 'product_count' => 21],
            ['name' => 'Souvenir Rombongan', 'slug' => 'souvenir-rombongan', 'art' => 'souvenir', 'image' => '/images/catalog/sarung.jpg', 'tagline' => 'Mulai 50 pcs', 'product_count' => 6],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
