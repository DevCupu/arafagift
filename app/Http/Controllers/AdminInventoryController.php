<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\Inventory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminInventoryController extends Controller
{
    private const MANUAL_TYPES = [
        'purchase', 'customer_return', 'manual_in',
        'internal_use', 'damage', 'loss', 'supplier_return', 'adjustment',
    ];

    public function index(): Response
    {
        return Inertia::render('admin/InventoryPage', [
            'products' => Product::with(['category', 'supplier', 'occasions'])
                ->orderBy('name')
                ->get()
                ->map->toCatalog()
                ->values(),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name'])->map(fn (Supplier $s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values(),
            'summary' => $this->summary(),
        ]);
    }

    public function record(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(self::MANUAL_TYPES)],
            ...($request->input('type') === 'adjustment'
                ? ['delta' => ['required', 'integer', 'not_in:0']]
                : ['qty' => ['required', 'integer', 'min:1']]),
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')],
            'note' => ['nullable', 'string', 'max:200'],
        ]);

        if (in_array($validated['type'], ['damage', 'loss', 'adjustment']) && blank($validated['note'] ?? null)) {
            return back()->withErrors(['note' => 'Catatan wajib diisi untuk jenis ini.']);
        }

        $direction = StockMovement::directionOf($validated['type']);
        $delta = $direction === 0 ? (int) $validated['delta'] : $direction * $validated['qty'];

        Inventory::lock($product, function (Product $locked) use ($delta, $validated, $request): void {
            Inventory::apply(
                $locked,
                $validated['type'],
                $delta,
                $request->user()->id,
                $validated['supplier_id'] ?? null,
                $validated['note'] ?? null,
            );
        });

        return back()->with('success', "Gerakan stok {$product->name} dicatat.");
    }

    public function threshold(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate(['low_stock_threshold' => ['required', 'integer', 'min:0']]);
        $product->update($validated);

        return back()->with('success', "Batas menipis {$product->name} jadi {$product->low_stock_threshold}.");
    }

    public function history(Product $product): JsonResponse
    {
        $movements = $product->movements()
            ->with(['user:id,name', 'supplier:id,name'])
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (StockMovement $movement): array => [
                'id' => $movement->id,
                'date' => $movement->created_at->toIso8601String(),
                'type' => $movement->type,
                'label' => $movement->label(),
                'inbound' => max($movement->delta, 0),
                'outbound' => max(-$movement->delta, 0),
                'balanceBefore' => $movement->balance_before,
                'balanceAfter' => $movement->balance_after,
                'documentNumber' => $movement->document_number,
                'orderNumber' => $movement->order_number,
                'note' => $movement->note,
                'operator' => $movement->user?->name,
                'supplier' => $movement->supplier?->name,
            ])
            ->values();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'unit' => $product->unit,
                'stock' => $product->stock,
            ],
            'movements' => $movements,
        ]);
    }

    public function export(): StreamedResponse
    {
        $filename = 'inventori-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');

            if ($out === false) {
                throw new RuntimeException('Tidak dapat membuka output untuk ekspor CSV.');
            }

            fputcsv($out, ['SKU', 'Nama', 'Kategori', 'Satuan', 'Lokasi', 'Supplier', 'Stok', 'Batas Menipis', 'Status', 'Nilai Stok (HPP)']);

            $products = Product::with(['category', 'supplier'])->orderBy('name')->cursor();

            foreach ($products as $product) {
                fputcsv($out, [
                    $product->sku,
                    $product->name,
                    $product->category?->name,
                    $product->unit,
                    $product->storage_location,
                    $product->supplier?->name,
                    $product->stock,
                    $product->low_stock_threshold,
                    $this->stateOf($product),
                    $product->cost !== null ? $product->cost * $product->stock : '',
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return array{skuCount: int, unitCount: int, stockValue: int, lowCount: int, outCount: int}
     */
    private function summary(): array
    {
        return [
            'skuCount' => Product::query()->count(),
            'unitCount' => (int) Product::query()->sum('stock'),
            'stockValue' => (int) Product::query()->selectRaw('COALESCE(SUM(cost * stock), 0) as total')->value('total'),
            'lowCount' => Product::query()->whereColumn('stock', '<=', 'low_stock_threshold')->where('stock', '>', 0)->count(),
            'outCount' => Product::query()->where('stock', 0)->count(),
        ];
    }

    private function stateOf(Product $product): string
    {
        return match (true) {
            $product->stock === 0 => 'Habis',
            $product->stock <= $product->low_stock_threshold => 'Menipis',
            default => 'Tersedia',
        };
    }
}
