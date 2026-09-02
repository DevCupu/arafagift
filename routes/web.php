<?php

use App\Http\Controllers\AccountAddressController;
use App\Http\Controllers\AccountOrderController;
use App\Http\Controllers\AccountProfileController;
use App\Http\Controllers\AdminCategoryController;
use App\Http\Controllers\AdminCustomerController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminInventoryController;
use App\Http\Controllers\AdminOrderController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AdminPromotionController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\AdminSettingsController;
use App\Http\Controllers\AdminSupplierController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ---------- SEO ----------
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [SitemapController::class, 'robots'])->name('robots');

// ---------- Storefront (DB-backed) ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/koleksi/{category?}', [CollectionController::class, 'index'])->name('collection');
Route::get('/pencarian', [ProductController::class, 'search'])->middleware('throttle:30,1,pencarian')->name('product.search');
Route::get('/produk/{product:slug}', [ProductController::class, 'show'])->name('product');
Route::get('/keranjang', fn () => Inertia::render('shop/CartPage'))->name('cart');
Route::middleware('auth')->group(function () {
    Route::get('/checkout', fn () => Inertia::render('shop/CheckoutPage'))->name('checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1,checkout')->name('checkout.store');
});
Route::get('/lacak-pesanan', [OrderTrackingController::class, 'index'])->middleware('throttle:20,1,lacak-pesanan')->name('order.track');
Route::get('/tentang', [PageController::class, 'about'])->name('about');
Route::get('/faq', [PageController::class, 'faq'])->name('faq');
Route::get('/legal/{slug}', [PageController::class, 'legal'])->name('legal');

// ---------- Customer account (authed customers only) ----------
Route::middleware('auth')->group(function () {
    Route::get('/akun', [AccountProfileController::class, 'edit'])->name('account');
    Route::put('/akun', [AccountProfileController::class, 'update'])->name('account.update');
    Route::put('/akun/kata-sandi', [AccountProfileController::class, 'updatePassword'])->name('account.password');

    Route::get('/akun/pesanan', [AccountOrderController::class, 'index'])->name('account.orders');
    Route::get('/akun/pesanan/{order:order_number}', [AccountOrderController::class, 'show'])->name('account.order');

    Route::get('/akun/alamat', [AccountAddressController::class, 'index'])->name('account.address');
    Route::post('/akun/alamat', [AccountAddressController::class, 'store'])->name('account.address.store');
    Route::put('/akun/alamat/{address}', [AccountAddressController::class, 'update'])->name('account.address.update');
    Route::delete('/akun/alamat/{address}', [AccountAddressController::class, 'destroy'])->name('account.address.destroy');
    Route::patch('/akun/alamat/{address}/utama', [AccountAddressController::class, 'setPrimary'])->name('account.address.primary');

    Route::get('/akun/wishlist', [WishlistController::class, 'index'])->name('account.wishlist');
    Route::post('/wishlist/{product}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

// ---------- Admin (authed + must be admin) ----------
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin');
    Route::get('/admin/pesanan', [AdminOrderController::class, 'index'])->name('admin.orders');
    Route::get('/admin/pesanan/baru', [AdminOrderController::class, 'create'])->name('admin.orders.new');
    Route::post('/admin/pesanan', [AdminOrderController::class, 'store'])->name('admin.orders.store');
    Route::get('/admin/pesanan/ekspor', [AdminOrderController::class, 'export'])->name('admin.orders.export');
    Route::patch('/admin/pesanan/{order_number}/pulihkan', [AdminOrderController::class, 'restore'])->name('admin.order.restore');
    Route::delete('/admin/pesanan/bulk', [AdminOrderController::class, 'bulkDestroy'])->name('admin.orders.bulkDestroy');
    Route::get('/admin/pesanan/{order:order_number}', [AdminOrderController::class, 'show'])->name('admin.order');
    Route::put('/admin/pesanan/{order:order_number}', [AdminOrderController::class, 'update'])->name('admin.order.update');
    Route::delete('/admin/pesanan/{order:order_number}', [AdminOrderController::class, 'destroy'])->name('admin.order.destroy');
    Route::get('/admin/produk', [AdminProductController::class, 'index'])->name('admin.products');
    Route::get('/admin/produk/baru', [AdminProductController::class, 'create'])->name('admin.products.new');
    Route::post('/admin/produk', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::delete('/admin/produk/bulk', [AdminProductController::class, 'bulkDestroy'])->name('admin.products.bulkDestroy');
    Route::get('/admin/produk/{product:slug}', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/admin/produk/{product:slug}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/admin/produk/{product:slug}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');
    Route::get('/admin/kategori', [AdminCategoryController::class, 'index'])->name('admin.categories');
    Route::post('/admin/kategori', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::delete('/admin/kategori/bulk', [AdminCategoryController::class, 'bulkDestroy'])->name('admin.categories.bulkDestroy');
    Route::put('/admin/kategori/{category:slug}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/admin/kategori/{category:slug}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');
    Route::get('/admin/pelanggan', [AdminCustomerController::class, 'index'])->name('admin.customers');
    Route::get('/admin/pelanggan/{customer}', [AdminCustomerController::class, 'show'])->name('admin.customer');
    Route::get('/admin/inventori', [AdminInventoryController::class, 'index'])->name('admin.inventory');
    Route::get('/admin/inventori/ekspor', [AdminInventoryController::class, 'export'])->name('admin.inventory.export');
    Route::patch('/admin/inventori/{product}/ambang', [AdminInventoryController::class, 'threshold'])->name('admin.inventory.threshold');
    Route::post('/admin/inventori/{product}/gerakan', [AdminInventoryController::class, 'record'])->name('admin.inventory.record');
    Route::get('/admin/inventori/{product}/riwayat', [AdminInventoryController::class, 'history'])->name('admin.inventory.history');
    Route::get('/admin/supplier', [AdminSupplierController::class, 'index'])->name('admin.suppliers');
    Route::post('/admin/supplier', [AdminSupplierController::class, 'store'])->name('admin.suppliers.store');
    Route::delete('/admin/supplier/bulk', [AdminSupplierController::class, 'bulkDestroy'])->name('admin.suppliers.bulkDestroy');
    Route::put('/admin/supplier/{supplier}', [AdminSupplierController::class, 'update'])->name('admin.suppliers.update');
    Route::delete('/admin/supplier/{supplier}', [AdminSupplierController::class, 'destroy'])->name('admin.suppliers.destroy');
    Route::get('/admin/promo', [AdminPromotionController::class, 'index'])->name('admin.promotions');
    Route::post('/admin/promo', [AdminPromotionController::class, 'store'])->name('admin.promotions.store');
    Route::delete('/admin/promo/bulk', [AdminPromotionController::class, 'bulkDestroy'])->name('admin.promotions.bulkDestroy');
    Route::delete('/admin/promo/{promotion}', [AdminPromotionController::class, 'destroy'])->name('admin.promotions.destroy');
    Route::get('/admin/konten', [ContentController::class, 'edit'])->name('admin.content');
    Route::put('/admin/konten', [ContentController::class, 'update'])->name('admin.content.update');
    Route::post('/admin/konten/testimoni', [ContentController::class, 'storeTestimonial'])->name('admin.content.testimonials.store');
    Route::put('/admin/konten/testimoni/{testimonial}', [ContentController::class, 'updateTestimonial'])->name('admin.content.testimonials.update');
    Route::delete('/admin/konten/testimoni/bulk', [ContentController::class, 'bulkDestroyTestimonials'])->name('admin.content.testimonials.bulkDestroy');
    Route::delete('/admin/konten/testimoni/{testimonial}', [ContentController::class, 'destroyTestimonial'])->name('admin.content.testimonials.destroy');
    Route::post('/admin/konten/faq', [ContentController::class, 'storeFaq'])->name('admin.content.faqs.store');
    Route::put('/admin/konten/faq/{faq}', [ContentController::class, 'updateFaq'])->name('admin.content.faqs.update');
    Route::delete('/admin/konten/faq/bulk', [ContentController::class, 'bulkDestroyFaqs'])->name('admin.content.faqs.bulkDestroy');
    Route::delete('/admin/konten/faq/{faq}', [ContentController::class, 'destroyFaq'])->name('admin.content.faqs.destroy');
    Route::patch('/admin/konten/faq/reorder', [ContentController::class, 'reorderFaq'])->name('admin.content.faqs.reorder');
    Route::patch('/admin/konten/unggulan/reorder', [ContentController::class, 'reorderFeatured'])->name('admin.content.featured.reorder');
    Route::patch('/admin/konten/unggulan/{product}/tambah', [ContentController::class, 'addFeatured'])->name('admin.content.featured.add');
    Route::patch('/admin/konten/unggulan/{product}/keluarkan', [ContentController::class, 'removeFeatured'])->name('admin.content.featured.remove');
    Route::get('/admin/laporan', [AdminReportController::class, 'index'])->name('admin.reports');
    Route::get('/admin/pengaturan', [AdminSettingsController::class, 'edit'])->name('admin.settings');
    Route::put('/admin/pengaturan', [AdminSettingsController::class, 'update'])->name('admin.settings.update');
});

Route::fallback(fn () => Inertia::render('shop/NotFoundPage')->toResponse(request())->setStatusCode(404));

require __DIR__.'/auth.php';
