<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AdminProductController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/ProductsPage', [
            'products' => Product::with(['category', 'supplier', 'occasions'])->orderBy('name')->get()->map->toCatalog()->values(),
            'categories' => Category::orderBy('id')->get()->map->toCatalog()->values(),
            'suppliers' => Supplier::orderBy('name')->get()->map->toCatalog()->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/ProductFormPage', [
            'product' => null,
            'categories' => Category::orderBy('id')->get()->map->toCatalog()->values(),
            'suppliers' => Supplier::orderBy('name')->get()->map->toCatalog()->values(),
        ]);
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/ProductFormPage', [
            'product' => $product->load('occasions')->toCatalog(),
            'categories' => Category::orderBy('id')->get()->map->toCatalog()->values(),
            'suppliers' => Supplier::orderBy('name')->get()->map->toCatalog()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $category = Category::query()->whereKey($validated['category_id'])->firstOrFail();

        $product = Product::create([
            ...$validated,
            'art' => $category->art,
            'image' => $this->storeImage($request),
        ]);

        return redirect()->route('admin.products.edit', $product)->with('success', 'Produk baru dibuat');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validated($request, $product);

        if ($path = $this->storeImage($request)) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $path;
        }

        $product->update($validated);

        return back()->with('success', 'Perubahan produk disimpan');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return back()->with('success', "{$product->name} dihapus");
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:products,id'],
        ]);

        $products = Product::whereIn('id', $validated['ids'])->get();

        foreach ($products as $product) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $product->delete();
        }

        return back()->with('success', $products->count().' produk dihapus');
    }

    public function inventory(): Response
    {
        return Inertia::render('admin/InventoryPage', [
            'products' => Product::with(['category', 'supplier', 'occasions'])->orderBy('name')->get()->map->toCatalog()->values(),
        ]);
    }

    public function updateStock(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate(['stock' => ['required', 'integer', 'min:0']]);
        $product->update($validated);

        return back()->with('success', "Stok {$product->name} diperbarui jadi {$product->stock}");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['required', 'string', 'max:150', Rule::unique('products', 'slug')->ignore($product)],
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($product)],
            'unit' => ['required', 'string', 'max:20'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')],
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:80'],
            'weight' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['active', 'draft', 'archived'])],
            'featured' => ['boolean'],
            'short' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
        ]);

        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('products', 'public');

        if ($path === false) {
            throw new RuntimeException('Gagal menyimpan gambar produk.');
        }

        return $path;
    }
}
