<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

trait ComputesSalesAggregates
{
    /**
     * @return array{label: string, value: string, delta: string, trend: string, note: string}
     */
    private function periodStat(string $label, float|int|string $current, float|int|string $previous, string $note, ?string $format = null): array
    {
        $currentValue = is_numeric($current) ? (float) $current : 0.0;
        $previousValue = is_numeric($previous) ? (float) $previous : 0.0;

        $delta = $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : ($currentValue > 0 ? 100 : 0);

        return [
            'label' => $label,
            'value' => $format === 'currency' ? 'Rp '.number_format($currentValue, 0, ',', '.') : number_format($currentValue, 0, ',', '.'),
            'delta' => ($delta >= 0 ? '+' : '−').number_format(abs($delta), 1, ',', '.').'%',
            'trend' => $delta >= 0 ? 'up' : 'down',
            'note' => $note,
        ];
    }

    /**
     * @return array<int, array{label: string, value: float|int}>
     */
    private function salesSeries(int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $byDay = Order::where('created_at', '>=', $start)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as day, SUM(total) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, $days - 1))->map(function (int $i) use ($start, $byDay): array {
            $date = $start->copy()->addDays($i);

            return [
                'label' => $date->format('d/m'),
                'value' => round(($byDay[$date->toDateString()] ?? 0) / 1_000_000, 1),
            ];
        })->all();
    }

    /**
     * @return array<int, array{name: string, sold: int, revenue: int, share: int}>
     */
    private function topProducts(int $limit = 5, int $days = 14): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.created_at', '>=', $start)
            ->where('orders.status', '!=', 'cancelled')
            ->selectRaw('order_items.name as name, SUM(order_items.qty) as sold, SUM(order_items.qty * order_items.price) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('revenue')
            ->limit($limit)
            ->get();

        $max = $rows->max('revenue') ?: 1;

        return $rows->map(fn ($row): array => [
            'name' => $row->name,
            'sold' => (int) $row->sold,
            'revenue' => (int) $row->revenue,
            'share' => (int) round((int) $row->revenue / $max * 100),
        ])->all();
    }
}
