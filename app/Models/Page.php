<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Landing page untuk iklan (Meta/Google Ads), disusun dari blok yang bisa
 * dipilih & diurutkan bebas lewat admin.
 *
 * @property string $slug
 * @property string $title
 * @property string $status
 * @property array<int, array<string, mixed>> $blocks
 */
#[Fillable(['slug', 'title', 'status', 'blocks'])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
        ];
    }

    /**
     * Blok yang benar-benar dirender: yang aktif saja, urutan sesuai array.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activeBlocks(): Collection
    {
        return collect($this->blocks ?? [])
            ->filter(fn (array $block): bool => $block['enabled'] ?? true)
            ->values();
    }
}
