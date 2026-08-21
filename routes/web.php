<?php

use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderConfirmationController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ---------- Storefront (DB-backed) ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/koleksi/{category?}', [CollectionController::class, 'index'])->name('collection');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('product');
Route::get('/keranjang', fn () => Inertia::render('shop/CartPage'))->name('cart');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store']);
Route::get('/checkout/selesai/{order:order_number}', [OrderConfirmationController::class, 'show'])->name('checkout.done');
Route::get('/tentang', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');

// ---------- Customer account (still static data — deferred phase) ----------
Route::get('/akun', fn () => Inertia::render('account/ProfilePage'))->name('account');
Route::get('/akun/pesanan', fn () => Inertia::render('account/OrdersPage'))->name('account.orders');
Route::get('/akun/pesanan/{id}', fn (string $id) => Inertia::render('account/OrderDetailPage', ['id' => $id]))->name('account.order');
Route::get('/akun/alamat', fn () => Inertia::render('account/AddressPage'))->name('account.address');
Route::get('/akun/wishlist', fn () => Inertia::render('account/WishlistPage'))->name('account.wishlist');

// ---------- Admin (authed + must be admin) ----------
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', fn () => Inertia::render('admin/DashboardPage'))->name('admin');
    Route::get('/admin/pesanan', fn () => Inertia::render('admin/OrdersPage'))->name('admin.orders');
    Route::get('/admin/pesanan/{id}', fn (string $id) => Inertia::render('admin/OrderDetailPage', ['id' => $id]))->name('admin.order');
    Route::get('/admin/produk', fn () => Inertia::render('admin/ProductsPage'))->name('admin.products');
    Route::get('/admin/produk/baru', fn () => Inertia::render('admin/ProductFormPage', ['slug' => null]))->name('admin.products.new');
    Route::get('/admin/produk/{slug}', fn (string $slug) => Inertia::render('admin/ProductFormPage', ['slug' => $slug]))->name('admin.products.edit');
    Route::get('/admin/kategori', fn () => Inertia::render('admin/CategoriesPage'))->name('admin.categories');
    Route::get('/admin/pelanggan', fn () => Inertia::render('admin/CustomersPage'))->name('admin.customers');
    Route::get('/admin/pelanggan/{id}', fn (string $id) => Inertia::render('admin/CustomerDetailPage', ['id' => $id]))->name('admin.customer');
    Route::get('/admin/inventori', fn () => Inertia::render('admin/InventoryPage'))->name('admin.inventory');
    Route::get('/admin/promo', fn () => Inertia::render('admin/PromotionsPage'))->name('admin.promotions');
    Route::get('/admin/konten', [ContentController::class, 'edit'])->name('admin.content');
    Route::put('/admin/konten', [ContentController::class, 'update'])->name('admin.content.update');
    Route::post('/admin/konten/testimoni', [ContentController::class, 'storeTestimonial'])->name('admin.content.testimonials.store');
    Route::delete('/admin/konten/testimoni/{testimonial}', [ContentController::class, 'destroyTestimonial'])->name('admin.content.testimonials.destroy');
    Route::patch('/admin/konten/unggulan/{product}', [ContentController::class, 'removeFeatured'])->name('admin.content.featured.remove');
    Route::get('/admin/laporan', fn () => Inertia::render('admin/ReportsPage'))->name('admin.reports');
    Route::get('/admin/pengaturan', fn () => Inertia::render('admin/SettingsPage'))->name('admin.settings');
});

Route::fallback(fn () => Inertia::render('shop/NotFoundPage')->toResponse(request())->setStatusCode(404));

require __DIR__.'/auth.php';
