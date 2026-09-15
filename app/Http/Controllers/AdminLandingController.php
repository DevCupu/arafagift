<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminLandingController extends Controller
{
    /**
     * Aturan per block_type, cerminan `resources/js/components/landing/blocks.js`.
     * Hanya tipe yang benar-benar dikirim yang divalidasi, jadi menambah tipe
     * blok = satu entry di sini + satu entry di blocks.js.
     *
     * @var array<string, array<string, array<int, string>>>
     */
    private const BLOCK_RULES = [
        'banner' => [
            'items' => ['present', 'array', 'max:6'],
            'items.*.image' => ['required', 'string', 'max:400'],
            'items.*.badge' => ['nullable', 'string', 'max:30'],
            'items.*.title' => ['nullable', 'string', 'max:120'],
            'items.*.ctaLabel' => ['nullable', 'string', 'max:40'],
            'items.*.href' => ['nullable', 'string', 'max:400'],
            'items.*.alt' => ['nullable', 'string', 'max:120'],
        ],
        'hero' => [
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'headline' => ['required', 'string', 'max:200'],
            'sub' => ['nullable', 'string', 'max:400'],
            'image' => ['nullable', 'string', 'max:400'],
            'cta.label' => ['nullable', 'string', 'max:60'],
            'cta.href' => ['nullable', 'string', 'max:400'],
        ],
        'nilai' => [
            'title' => ['nullable', 'string', 'max:120'],
            'items' => ['present', 'array', 'max:5'],
            'items.*.icon' => ['required', 'string', 'max:40'],
            'items.*.image' => ['nullable', 'string', 'max:400'],
            'items.*.title' => ['required', 'string', 'max:60'],
            'items.*.body' => ['nullable', 'string', 'max:200'],
        ],
        'produk_unggulan' => [
            'eyebrow' => ['nullable', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:120'],
            'intro' => ['nullable', 'string', 'max:240'],
            'productIds' => ['present', 'array', 'max:8'],
            'productIds.*' => ['integer', 'exists:products,id'],
            // Untuk produk yang belum ada di katalog: diketik manual, tidak mereferensi Product.
            'customItems' => ['present', 'array', 'max:8'],
            'customItems.*.name' => ['required', 'string', 'max:120'],
            'customItems.*.category' => ['nullable', 'string', 'max:60'],
            'customItems.*.price' => ['nullable', 'integer', 'min:0'],
            'customItems.*.comparePrice' => ['nullable', 'integer', 'min:0'],
            'customItems.*.image' => ['nullable', 'string', 'max:400'],
            'customItems.*.badge' => ['nullable', 'string', 'max:30'],
            'customItems.*.rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'customItems.*.reviews' => ['nullable', 'integer', 'min:0'],
            'customItems.*.description' => ['nullable', 'string', 'max:700'],
            'customItems.*.includes' => ['nullable', 'array', 'max:6'],
            'customItems.*.includes.*.value' => ['required', 'string', 'max:100'],
            'customItems.*.details' => ['nullable', 'array', 'max:6'],
            'customItems.*.details.*.label' => ['required', 'string', 'max:60'],
            'customItems.*.details.*.value' => ['required', 'string', 'max:120'],
        ],
        'testimoni' => [
            'title' => ['nullable', 'string', 'max:120'],
            'items' => ['present', 'array', 'max:6'],
            'items.*.name' => ['required', 'string', 'max:80'],
            'items.*.role' => ['nullable', 'string', 'max:80'],
            'items.*.quote' => ['required', 'string', 'max:400'],
            'items.*.avatar' => ['nullable', 'string', 'max:400'],
        ],
        'faq' => [
            'title' => ['nullable', 'string', 'max:120'],
            'items' => ['present', 'array', 'max:8'],
            'items.*.q' => ['required', 'string', 'max:200'],
            'items.*.a' => ['required', 'string', 'max:800'],
        ],
        'cta' => [
            'headline' => ['required', 'string', 'max:160'],
            'sub' => ['nullable', 'string', 'max:300'],
            'note' => ['nullable', 'string', 'max:120'],
            'cta.label' => ['nullable', 'string', 'max:60'],
            'cta.href' => ['nullable', 'string', 'max:400'],
        ],
        'popup' => [
            'image' => ['nullable', 'string', 'max:400'],
            'badge' => ['nullable', 'string', 'max:30'],
            'title' => ['required', 'string', 'max:120'],
            'sub' => ['nullable', 'string', 'max:300'],
            'ctaLabel' => ['nullable', 'string', 'max:40'],
            'href' => ['nullable', 'string', 'max:400'],
            'delaySeconds' => ['nullable', 'integer', 'min:0', 'max:60'],
        ],
    ];

    public function index(): Response
    {
        return Inertia::render('admin/LandingsPage', [
            'pages' => Page::orderByDesc('updated_at')->get()->map(fn (Page $page): array => [
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
                'blockCount' => count($page->blocks ?? []),
                'updatedAt' => $page->updated_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/LandingFormPage', [
            'page' => null,
            'products' => $this->productOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $page = Page::create($this->validated($request));

        return to_route('admin.landings.edit', $page->slug)->with('success', 'Landing dibuat');
    }

    public function edit(Page $page): Response
    {
        return Inertia::render('admin/LandingFormPage', [
            'page' => [
                'slug' => $page->slug,
                'title' => $page->title,
                'status' => $page->status,
                'blocks' => $page->blocks ?? [],
            ],
            'products' => $this->productOptions(),
        ]);
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $page->update($this->validated($request, $page));

        return back()->with('success', 'Landing disimpan');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return to_route('admin.landings')->with('success', "{$page->title} dihapus");
    }

    /**
     * Upload berdiri sendiri dan mengembalikan URL, supaya simpan halaman tetap
     * JSON murni (tanpa multipart / _method spoofing seperti form admin lain).
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:4096'],
        ]);

        $path = $request->file('image')->store('landing', 'public');

        if ($path === false) {
            return response()->json(['message' => 'Gagal menyimpan gambar.'], 422);
        }

        return response()->json(['url' => Storage::disk('public')->url($path)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:120'],
            'slug' => [
                'required', 'string', 'max:60', 'regex:/^[a-z0-9][a-z0-9-]*$/',
                Rule::notIn($this->reservedSlugs()),
                Rule::unique('pages', 'slug')->ignore($page),
            ],
            'status' => ['required', Rule::in(['draft', 'publish'])],
            'blocks' => ['present', 'array', 'max:20'],
            'blocks.*.type' => ['required', Rule::in(array_keys(self::BLOCK_RULES))],
            'blocks.*.enabled' => ['boolean'],
            'blocks.*.content' => ['required', 'array'],
        ];

        // Aturan per-blok dibangun hanya untuk blok yang benar-benar dikirim,
        // supaya pesan error menunjuk ke index blok yang tepat.
        foreach ((array) $request->input('blocks', []) as $i => $block) {
            foreach (self::BLOCK_RULES[$block['type'] ?? ''] ?? [] as $field => $fieldRules) {
                $rules["blocks.{$i}.content.{$field}"] = $fieldRules;
            }
        }

        $validator = Validator::make($request->all(), $rules, [], [
            'slug' => 'alamat halaman',
            'blocks' => 'blok',
        ]);

        // Total produk (katalog + manual) tampil dalam satu grid: batasi gabungan,
        // bukan cuma tiap sumber sendiri-sendiri, supaya layout tetap rapi.
        $validator->after(function ($validator) use ($request): void {
            foreach ((array) $request->input('blocks', []) as $i => $block) {
                if (($block['type'] ?? null) !== 'produk_unggulan') {
                    continue;
                }
                $total = count($block['content']['productIds'] ?? []) + count($block['content']['customItems'] ?? []);
                if ($total > 8) {
                    $validator->errors()->add("blocks.{$i}.content.productIds", 'Total produk katalog + manual maksimal 8.');
                }
            }
        });

        $data = $validator->validate();

        // Buang key liar yang tidak dideklarasikan di BLOCK_RULES: content masuk
        // mentah ke JSON lalu dirender publik, jadi jangan simpan apa pun yang
        // tidak kita kenali.
        $data['blocks'] = array_map(fn (array $block): array => [
            'type' => $block['type'],
            'enabled' => (bool) ($block['enabled'] ?? true),
            'content' => $this->pruneContent($block['type'], $block['content'] ?? []),
        ], $data['blocks']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private function pruneContent(string $type, array $content): array
    {
        $allowed = collect(array_keys(self::BLOCK_RULES[$type]))
            ->map(fn (string $field): string => explode('.', $field)[0])
            ->unique()
            ->all();

        return collect($content)->only($allowed)->all();
    }

    /**
     * Slug landing tidak boleh membajak route yang sudah terdaftar. Dibaca dari
     * router, bukan daftar manual, supaya ikut terpelihara sendiri.
     *
     * @return array<int, string>
     */
    private function reservedSlugs(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->map(fn (RoutingRoute $route): string => explode('/', $route->uri())[0])
            ->filter(fn (string $segment): bool => $segment !== '' && ! str_starts_with($segment, '{'))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productOptions(): array
    {
        return Product::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'image'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->imageUrl(),
            ])
            ->all();
    }
}
