<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\StoreSettingsCache;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Invoice HTML yang siap dicetak / disimpan sebagai PDF lewat browser. */
class InvoiceController extends Controller
{
    public function account(Request $request, Order $order): View
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        return $this->render($order);
    }

    public function token(string $token): View
    {
        $order = Order::where('tracking_token', $token)->first();
        abort_unless($order, 404);

        return $this->render($order);
    }

    public function admin(Order $order): View
    {
        return $this->render($order);
    }

    private function render(Order $order): View
    {
        return view('invoice', [
            'order' => $order->load(['items', 'paymentMethod']),
            'store' => StoreSettingsCache::store(),
        ]);
    }
}
