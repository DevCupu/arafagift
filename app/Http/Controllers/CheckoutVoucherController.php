<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\OrderPricing;
use App\Services\VoucherPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutVoucherController extends Controller
{
    public function check(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $ids = array_values(array_unique(array_column($validated['items'], 'id')));
        if (Product::whereIn('id', $ids)->count() !== count($ids)) {
            throw ValidationException::withMessages(['voucher' => 'Salah satu produk tidak tersedia.']);
        }

        $priced = OrderPricing::priceItems($validated['items']);
        $voucher = VoucherPricing::resolve($validated['code'], $priced['lines']);

        return response()->json([
            'valid' => true,
            'code' => $voucher['promotion']->code,
            'discount' => $voucher['discount'],
            'label' => $voucher['promotion']->type,
            'subtotal' => $priced['subtotal'],
        ]);
    }
}
