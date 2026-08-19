import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  // ---------- Storefront ----------
  { path: '/', name: 'home', component: () => import('@/pages/shop/HomePage.vue') },
  { path: '/koleksi', name: 'collection', component: () => import('@/pages/shop/CollectionPage.vue') },
  { path: '/koleksi/:category', name: 'collection-category', component: () => import('@/pages/shop/CollectionPage.vue') },
  { path: '/produk/:slug', name: 'product', component: () => import('@/pages/shop/ProductPage.vue') },
  { path: '/keranjang', name: 'cart', component: () => import('@/pages/shop/CartPage.vue') },
  { path: '/checkout', name: 'checkout', meta: { layout: 'bare' }, component: () => import('@/pages/shop/CheckoutPage.vue') },
  { path: '/checkout/selesai', name: 'order-done', component: () => import('@/pages/shop/OrderDonePage.vue') },
  { path: '/tentang', name: 'about', component: () => import('@/pages/shop/AboutPage.vue') },
  { path: '/faq', name: 'faq', component: () => import('@/pages/shop/FaqPage.vue') },

  // ---------- Customer account ----------
  { path: '/akun', name: 'account', component: () => import('@/pages/account/ProfilePage.vue') },
  { path: '/akun/pesanan', name: 'account-orders', component: () => import('@/pages/account/OrdersPage.vue') },
  { path: '/akun/pesanan/:id', name: 'account-order', component: () => import('@/pages/account/OrderDetailPage.vue') },
  { path: '/akun/alamat', name: 'account-address', component: () => import('@/pages/account/AddressPage.vue') },
  { path: '/akun/wishlist', name: 'account-wishlist', component: () => import('@/pages/account/WishlistPage.vue') },

  // ---------- Admin ----------
  { path: '/admin', name: 'admin', meta: { layout: 'admin' }, component: () => import('@/pages/admin/DashboardPage.vue') },
  { path: '/admin/pesanan', name: 'admin-orders', meta: { layout: 'admin' }, component: () => import('@/pages/admin/OrdersPage.vue') },
  { path: '/admin/pesanan/:id', name: 'admin-order', meta: { layout: 'admin' }, component: () => import('@/pages/admin/OrderDetailPage.vue') },
  { path: '/admin/produk', name: 'admin-products', meta: { layout: 'admin' }, component: () => import('@/pages/admin/ProductsPage.vue') },
  { path: '/admin/produk/baru', name: 'admin-product-new', meta: { layout: 'admin' }, component: () => import('@/pages/admin/ProductFormPage.vue') },
  { path: '/admin/produk/:slug', name: 'admin-product-edit', meta: { layout: 'admin' }, component: () => import('@/pages/admin/ProductFormPage.vue') },
  { path: '/admin/kategori', name: 'admin-categories', meta: { layout: 'admin' }, component: () => import('@/pages/admin/CategoriesPage.vue') },
  { path: '/admin/pelanggan', name: 'admin-customers', meta: { layout: 'admin' }, component: () => import('@/pages/admin/CustomersPage.vue') },
  { path: '/admin/pelanggan/:id', name: 'admin-customer', meta: { layout: 'admin' }, component: () => import('@/pages/admin/CustomerDetailPage.vue') },
  { path: '/admin/inventori', name: 'admin-inventory', meta: { layout: 'admin' }, component: () => import('@/pages/admin/InventoryPage.vue') },
  { path: '/admin/promo', name: 'admin-promotions', meta: { layout: 'admin' }, component: () => import('@/pages/admin/PromotionsPage.vue') },
  { path: '/admin/konten', name: 'admin-content', meta: { layout: 'admin' }, component: () => import('@/pages/admin/ContentPage.vue') },
  { path: '/admin/laporan', name: 'admin-reports', meta: { layout: 'admin' }, component: () => import('@/pages/admin/ReportsPage.vue') },
  { path: '/admin/pengaturan', name: 'admin-settings', meta: { layout: 'admin' }, component: () => import('@/pages/admin/SettingsPage.vue') },

  { path: '/:pathMatch(.*)*', name: 'not-found', component: () => import('@/pages/shop/NotFoundPage.vue') },
]

export default createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, saved) {
    if (saved) return saved
    if (to.hash) return { el: to.hash, behavior: 'smooth' }
    return { top: 0 }
  },
})
