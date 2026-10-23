<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class GiftSetCategorySeeder extends Seeder
{
    public function run(): void
    {
        // firstOrCreate menjaga nama, foto, dan tagline yang mungkin sudah
        // diubah melalui admin. Produk tidak dipindah atau dibuat di sini.
        Category::firstOrCreate(
            ['slug' => 'gift-set'],
            [
                'name' => 'Gift Set',
                'art' => 'giftset',
                'tagline' => 'Siap diberikan',
                'product_count' => 0,
            ],
        );
    }
}
