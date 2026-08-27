<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ComputesSalesAggregates;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminReportController extends Controller
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

        $returningUserIds = (clone $current)->whereNotNull('user_id')
            ->whereIn('user_id', fn ($q) => $q->select('user_id')->from('orders')->where('created_at', '<', $periodStart)->whereNotNull('user_id'))
            ->distinct()->pluck('user_id');
        $currentBuyers = (clone $current)->whereNotNull('user_id')->distinct()->count('user_id');
        $returningShare = $currentBuyers ? (int) round($returningUserIds->count() / $currentBuyers * 100) : 0;

        return Inertia::render('admin/ReportsPage', [
            'stats' => [
                $this->periodStat('Pendapatan 14 hari', $currentSales, $previousSales, 'vs periode lalu', 'currency'),
                $this->periodStat('Pesanan', $currentCount, $previousCount, 'vs periode lalu'),
                $this->periodStat(
                    'Nilai rata-rata',
                    $currentCount ? $currentSales / $currentCount : 0,
                    $previousCount ? $previousSales / $previousCount : 0,
                    'vs periode lalu',
                    'currency',
                ),
                [
                    'label' => 'Pembeli kembali',
                    'value' => "{$returningShare}%",
                    'delta' => $returningShare >= 50 ? 'Mayoritas' : 'Minoritas',
                    'trend' => $returningShare >= 50 ? 'up' : 'down',
                    'note' => 'dari pembeli terdaftar periode ini',
                ],
            ],
            'salesSeries' => $this->salesSeries(),
            'topProducts' => $this->topProducts(),
            'channels' => Order::where('created_at', '>=', $periodStart)
                ->select('channel', DB::raw('count(*) as total'))
                ->groupBy('channel')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) use ($currentCount) {
                    return [
                        'label' => $row->channel,
                        'share' => $currentCount ? (int) round($row->total / $currentCount * 100) : 0,
                    ];
                }),
        ]);
    }
}
