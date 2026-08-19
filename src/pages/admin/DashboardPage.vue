<script setup>
import { computed } from 'vue'
import { ArrowUpRight } from 'lucide-vue-next'
import StatCard from '@/components/admin/StatCard.vue'
import SalesChart from '@/components/admin/SalesChart.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { dashboardStats, orders, orderTotal, salesSeries, topProducts } from '@/data/admin'
import { products } from '@/data/catalog'
import { formatDateTime, formatIDR, formatShort } from '@/composables/useFormat'

const recent = orders.slice(0, 5)
const lowStock = computed(() =>
  products.filter((p) => p.stock <= p.lowStock).sort((a, b) => a.stock - b.stock).slice(0, 5),
)
const customerSplit = [
  { label: 'Pelanggan baru', value: 62, count: 21 },
  { label: 'Pelanggan lama', value: 38, count: 13 },
]
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="eyebrow">Selasa, 18 Agustus 2026</p>
        <h1 class="mt-4 text-[2.1rem] leading-none">Ringkasan hari ini</h1>
      </div>
      <div class="flex gap-3">
        <AppButton to="/admin/produk/baru" variant="quiet" size="sm">Tambah produk</AppButton>
        <AppButton to="/admin/pesanan" size="sm">Lihat semua pesanan</AppButton>
      </div>
    </header>

    <!-- KPI -->
    <div class="mt-8 grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-2 xl:grid-cols-4">
      <StatCard v-for="s in dashboardStats" :key="s.label" :stat="s" />
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.7fr_1fr]">
      <!-- Grafik -->
      <section class="border border-line bg-surface p-6 sm:p-7">
        <div class="flex flex-wrap items-end justify-between gap-4">
          <div>
            <h2 class="font-display text-2xl">Penjualan 14 hari</h2>
            <p class="mt-1.5 text-[0.8rem] text-muted">Total Rp 171,2 jt · naik 23% dibanding periode sebelumnya</p>
          </div>
          <select class="border border-line bg-surface px-3 py-2 text-[0.78rem] text-forest focus:border-olive focus:outline-none" aria-label="Rentang waktu">
            <option>14 hari terakhir</option>
            <option>30 hari terakhir</option>
            <option>Tahun ini</option>
          </select>
        </div>
        <div class="mt-8"><SalesChart :series="salesSeries" /></div>
      </section>

      <!-- Pelanggan -->
      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Pelanggan</h2>
        <p class="mt-1.5 text-[0.8rem] text-muted">34 pesanan hari ini dari 34 pembeli.</p>
        <ul class="mt-7 space-y-6">
          <li v-for="c in customerSplit" :key="c.label">
            <div class="flex items-baseline justify-between">
              <span class="text-[0.85rem] text-forest">{{ c.label }}</span>
              <span class="text-[0.78rem] text-muted">{{ c.count }} orang · {{ c.value }}%</span>
            </div>
            <div class="mt-2.5 h-1 w-full bg-line">
              <div class="h-full bg-forest" :style="{ width: `${c.value}%` }" />
            </div>
          </li>
        </ul>
        <div class="mt-8 border-t border-line pt-6">
          <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Pesanan rombongan bulan ini</p>
          <p class="mt-3 font-display text-[2rem] leading-none text-forest">9</p>
          <p class="mt-2 text-[0.8rem] text-muted">Rata-rata 186 pcs per rombongan.</p>
        </div>
      </section>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1.7fr_1fr]">
      <!-- Pesanan terbaru -->
      <section class="border border-line bg-surface">
        <div class="flex items-center justify-between border-b border-line px-6 py-5">
          <h2 class="font-display text-2xl">Pesanan terbaru</h2>
          <router-link to="/admin/pesanan" class="link-underline text-[0.8rem] text-forest">Semua pesanan</router-link>
        </div>
        <ul class="divide-y divide-line">
          <li v-for="o in recent" :key="o.id">
            <router-link :to="`/admin/pesanan/${o.id}`" class="flex items-center gap-4 px-6 py-4 transition hover:bg-ivory/70">
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory">
                <ProductArt :art="o.items[0].art" :tone="o.items.length" />
              </span>
              <span class="min-w-0 flex-1">
                <span class="block truncate text-[0.88rem] text-forest">{{ o.customer }}</span>
                <span class="mt-0.5 block text-[0.72rem] text-muted">{{ o.id }} · {{ formatDateTime(o.date) }}</span>
              </span>
              <StatusPill :status="o.status" class="hidden sm:inline-flex" />
              <span class="w-28 text-right text-[0.88rem] text-forest">{{ formatIDR(orderTotal(o)) }}</span>
              <ArrowUpRight class="h-4 w-4 flex-none text-muted transition group-hover:text-forest" />
            </router-link>
          </li>
        </ul>
      </section>

      <div class="space-y-6">
        <!-- Stok menipis -->
        <section class="border border-line bg-surface">
          <div class="flex items-center justify-between border-b border-line px-6 py-5">
            <h2 class="font-display text-2xl">Stok menipis</h2>
            <router-link to="/admin/inventori" class="link-underline text-[0.8rem] text-forest">Inventori</router-link>
          </div>
          <ul class="divide-y divide-line">
            <li v-for="p in lowStock" :key="p.id" class="flex items-center gap-4 px-6 py-3.5">
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
        </section>

        <!-- Produk terlaris -->
        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Produk terlaris</h2>
          <ul class="mt-6 space-y-5">
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
        </section>
      </div>
    </div>
  </div>
</template>
