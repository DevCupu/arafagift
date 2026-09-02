<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminSupplierController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/SuppliersPage', [
            'suppliers' => Supplier::withCount('products')->orderBy('name')->get()->map->toCatalog()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Supplier::create($this->validated($request));

        return back()->with('success', 'Supplier baru ditambahkan');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request, $supplier));

        return back()->with('success', 'Data supplier diperbarui');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->products()->exists()) {
            return back()->withErrors(['supplier' => 'Lepaskan atau ganti supplier pada produk yang memakainya dulu.']);
        }

        $supplier->delete();

        return back()->with('success', "{$supplier->name} dihapus");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:suppliers,id'],
        ]);

        $suppliers = Supplier::withCount('products')->whereIn('id', $validated['ids'])->get();
        $blocked = $suppliers->filter(fn (Supplier $s) => $s->products_count > 0);
        $deletable = $suppliers->reject(fn (Supplier $s) => $s->products_count > 0);

        $deletable->each->delete();

        if ($blocked->isNotEmpty()) {
            $prefix = $deletable->isNotEmpty() ? "{$deletable->count()} supplier dihapus. " : '';

            return back()->withErrors([
                'supplier' => $prefix.'Dilewati karena masih dipakai produk: '.$blocked->pluck('name')->implode(', ').'.',
            ]);
        }

        return back()->with('success', "{$deletable->count()} supplier dihapus");
    }

    /**
     * @return array<string, string|null>
     */
    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
    }
}
