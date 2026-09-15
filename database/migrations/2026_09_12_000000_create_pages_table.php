<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->enum('status', ['draft', 'publish'])->default('draft');
            // ponytail: urutan blok = urutan array. Tabel page_blocks terpisah tidak
            // memberi apa-apa karena blok tidak pernah di-query lepas dari halamannya.
            $table->json('blocks');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
