<?php

namespace Database\Seeders;

use App\Models\Occasion;
use Illuminate\Database\Seeder;

class OccasionSeeder extends Seeder
{
    public function run(): void
    {
        $occasions = [
            ['slug' => 'orang-tua', 'title' => 'Untuk Orang Tua', 'note' => 'Hadiah yang terasa hormat', 'art' => 'sajadah'],
            ['slug' => 'keluarga', 'title' => 'Untuk Keluarga', 'note' => 'Satu box, semua kebagian', 'art' => 'giftset'],
            ['slug' => 'sahabat', 'title' => 'Untuk Sahabat', 'note' => 'Ringan, hangat, berkesan', 'art' => 'kurma'],
            ['slug' => 'guru', 'title' => 'Untuk Guru', 'note' => 'Sederhana tapi berkelas', 'art' => 'tasbih'],
            ['slug' => 'rombongan', 'title' => 'Untuk Rombongan', 'note' => 'Seragam, rapi, hemat', 'art' => 'souvenir'],
            ['slug' => 'tamu', 'title' => 'Untuk Tamu Walimah', 'note' => 'Dibawa pulang dengan senang', 'art' => 'madu'],
        ];

        foreach ($occasions as $occasion) {
            Occasion::create($occasion);
        }
    }
}
