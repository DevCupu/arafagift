<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminCategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/CategoriesPage', [
            'categories' => Category::withCount('products')->orderBy('id')->get()->map->toCatalog()->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create([
            ...$this->validated($request),
            'image' => $this->storeImage($request),
        ]);

        return back()->with('success', 'Kategori baru dibuat');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validated($request, $category);

        if ($path = $this->storeImage($request)) {
            if ($category->image && ! str_starts_with($category->image, '/')) {
                Storage::disk('public')->delete($category->image);
            }
            $validated['image'] = $path;
        }

        $category->update($validated);

        return back()->with('success', 'Kategori diperbarui');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Pindahkan atau hapus dulu produk di kategori ini sebelum menghapusnya.']);
        }

        if ($category->image && ! str_starts_with($category->image, '/')) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return back()->with('success', "{$category->name} dihapus");
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['required', 'string', 'max:80', Rule::unique('categories', 'slug')->ignore($category)],
            'art' => ['required', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:120'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        unset($data['image']);

        return $data;
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('categories', 'public');

        return $path === false ? null : $path;
    }
}
