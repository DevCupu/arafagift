<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight } from 'lucide-vue-next'
import StatCard from '@/components/admin/StatCard.vue'
import SalesChart from '@/components/admin/SalesChart.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { orderTotal } from '@/data/admin'
import { formatDate, formatDateTime, formatIDR, formatShort } from '@/composables/useFormat'

defineProps({
  stats: { type: Array, required: true },
  salesSeries: { type: Array, required: true },
  topProducts: { type: Array, required: true },
  recentOrders: { type: Array, required: true },
  lowStockProducts: { type: Array, required: true },
})

const today = formatDate(new Date(), { weekday: 'long' })
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="eyebrow">{{ today }}</p>
        <h1 class="mt-4 text-[2.1rem] leading-none">Ringkasan 14 hari terakhir</h1>
      </div>
      <div class="flex gap-3">
        <AppButton to="/admin/produk/baru" variant="quiet" size="sm">Tambah produk</AppButton>
        <AppButton to="/admin/pesanan" size="sm">Lihat semua pesanan</AppButton>
      </div>
    </header>

    <!-- KPI -->
    <div class="mt-8 grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-2 xl:grid-cols-4">
      <StatCard v-for="s in stats" :key="s.label" :stat="s" />
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.7fr_1fr]">
      <!-- Grafik -->
      <section class="border border-line bg-surface p-6 sm:p-7">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <h2 class="font-display text-2xl">Penjualan 14 hari</h2>
        </div>
        <div class="mt-8"><SalesChart :series="salesSeries" /></div>
      </section>

      <!-- Produk terlaris -->
      <section class="border border-line bg-surface p-6">
        <h2 class="font-display text-2xl">Produk terlaris</h2>
        <ul v-if="topProducts.length" class="mt-6 space-y-5">
          <li v-for="p in topProducts" :key="p.name">
            <div class="flex items-baseline justify-between gap-4">
              <span class="truncate text-[0.85rem] text-forest">{{ p.name }}</span>
              <span class="flex-none text-[0.78rem] text-muted">{{ formatShort(p.revenue) }}</span>
            </div>
            <div class="mt-2 flex items-center gap-3">
              <div class="h-1 flex-1 bg-line">
                <div class="h-full bg-gold" :style="{ width: `${p.share}%` }" />
              </div>
              <span class="w-16 text-right text-[0.72rem] text-muted">{{ p.sold }} pcs</span>
            </div>
          </li>
        </ul>
        <p v-else class="mt-6 text-[0.85rem] text-muted">Belum ada penjualan di periode ini.</p>
      </section>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.7fr_1fr]">
      <!-- Pesanan terbaru -->
      <section class="border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-6 py-5">
          <h2 class="font-display text-2xl">Pesanan terbaru</h2>
          <Link href="/admin/pesanan" class="link-underline text-[0.8rem] text-forest">Semua pesanan</Link>
        </div>
        <ul v-if="recentOrders.length" class="divide-y divide-line">
          <li v-for="o in recentOrders" :key="o.id">
            <Link :href="`/admin/pesanan/${o.id}`" class="flex items-center gap-4 px-6 py-4 transition hover:bg-ivory/70">
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory">
                <ProductArt :art="o.items[0]?.art" :tone="o.items.length" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block truncate text-[0.88rem] text-forest">{{ o.customer }}</span>
                <span class="mt-0.5 block text-[0.72rem] text-muted">{{ o.id }} · {{ formatDateTime(o.date) }}</span>
              </span>
              <StatusPill :status="o.status" class="hidden sm:inline-flex" />
              <span class="w-28 text-right text-[0.88rem] text-forest">{{ formatIDR(orderTotal(o)) }}</span>
              <ArrowUpRight class="h-4 w-4 flex-none text-muted transition group-hover:text-forest" />
            </Link>
          </li>
        </ul>
        <p v-else class="px-6 py-10 text-center text-[0.85rem] text-muted">Belum ada pesanan.</p>
      </section>

      <!-- Stok menipis -->
      <section class="border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-6 py-5">
          <h2 class="font-display text-2xl">Stok menipis</h2>
          <Link href="/admin/inventori" class="link-underline text-[0.8rem] text-forest">Inventori</Link>
        </div>
        <ul v-if="lowStockProducts.length" class="divide-y divide-line">
          <li v-for="p in lowStockProducts" :key="p.id" class="flex items-center gap-4 px-6 py-3.5">
            <span class="min-w-0 flex-1">
              <span class="block truncate text-[0.85rem] text-forest">{{ p.name }}</span>
              <span class="text-[0.72rem] text-muted">{{ p.sku }}</span>
            </span>
            <StatusPill
              :label="p.stock === 0 ? 'Habis' : `${p.stock} tersisa`"
              :tone="p.stock === 0 ? 'danger' : 'warn'"
            />
          </li>
        </ul>
        <p v-else class="px-6 py-10 text-center text-[0.85rem] text-muted">Semua stok aman.</p>
      </section>
    </div>
  </div>
</template>
