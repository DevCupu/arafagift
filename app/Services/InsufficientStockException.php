<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class InsufficientStockException extends RuntimeException
{
    public function __construct(
        public readonly Product $product,
        public readonly int $shortage,
    ) {
        parent::__construct("Stok {$product->name} kurang {$shortage} unit.");
    }

    public function render(Request $request): RedirectResponse
    {
        return back()->withErrors([
            'stock' => $this->getMessage().' Tambah stok dulu atau sesuaikan jumlahnya.',
        ]);
    }
}
