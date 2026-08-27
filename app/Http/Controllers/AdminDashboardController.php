<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesSalesAggregates;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    use ComputesSalesAggregates;

    public function index(): Response
    {
        $periodStart = now()->subDays(13)->startOfDay();
        $prevPeriodStart = now()->subDays(27)->startOfDay();

        $current = Order::where('created_at', '>=', $periodStart);
        $previous = Order::whereBetween('created_at', [$prevPeriodStart, $periodStart]);

        $currentSales = (clone $current)->where('status', '!=', 'cancelled')->sum('total');
        $previousSales = (clone $previous)->where('status', '!=', 'cancelled')->sum('total');
        $currentCount = (clone $current)->count();
        $previousCount = (clone $previous)->count();
        $currentQty = OrderItem::whereHas('order', fn ($q) => $q->where('created_at', '>=', $periodStart))->sum('qty');
        $previousQty = OrderItem::whereHas('order', fn ($q) => $q->whereBetween('created_at', [$prevPeriodStart, $periodStart]))->sum('qty');
        $pendingCount = Order::where('status', 'pending')->count();

        return Inertia::render('admin/DashboardPage', [
            'stats' => [
                $this->periodStat('Penjualan 14 hari', $currentSales, $previousSales, 'vs 14 hari sebelumnya', 'currency'),
                $this->periodStat('Pesanan', $currentCount, $previousCount, "{$pendingCount} menunggu bayar"),
                $this->periodStat('Produk terjual', $currentQty, $previousQty, 'termasuk rombongan'),
                $this->periodStat(
                    'Rata-rata pesanan',
                    $currentCount ? $currentSales / $currentCount : 0,
                    $previousCount ? $previousSales / $previousCount : 0,
                    'vs periode lalu',
                    'currency',
                ),
            ],
            'salesSeries' => $this->salesSeries(),
            'topProducts' => $this->topProducts(),
            'recentOrders' => Order::with(['items', 'shippingMethod', 'paymentMethod'])->latest()->limit(5)->get()->map->toCatalog()->values(),
            'lowStockProducts' => Product::with('category')->whereColumn('stock', '<=', 'low_stock_threshold')->orderBy('stock')->limit(5)->get()->map->toCatalog()->values(),
        ]);
    }
}
