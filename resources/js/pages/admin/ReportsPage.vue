<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import SalesChart from '@/components/admin/SalesChart.vue'
import StatCard from '@/components/admin/StatCard.vue'
import { salesSeries, topProducts } from '@/data/admin'
import { formatShort } from '@/composables/useFormat'

const stats = [
  { label: 'Pendapatan 14 hari', value: 'Rp 171,2 jt', delta: '+23,1%', trend: 'up', note: 'vs periode lalu' },
  { label: 'Pesanan', value: '268', delta: '+19', trend: 'up', note: 'vs periode lalu' },
  { label: 'Nilai rata-rata', value: 'Rp 638 rb', delta: '−2,1%', trend: 'down', note: 'vs periode lalu' },
  { label: 'Pembeli kembali', value: '41%', delta: '+4,2%', trend: 'up', note: 'vs periode lalu' },
]

const channels = [
  { label: 'Website', share: 64 },
  { label: 'WhatsApp', share: 27 },
  { label: 'Instagram', share: 9 },
]
</script>

<template>
  <div>
    <header>
      <h1 class="text-[2.1rem] leading-none">Laporan</h1>
      <p class="mt-3 text-[0.85rem] text-muted">Ringkasan performa toko 14 hari terakhir.</p>
    </header>

    <div class="mt-8 grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-2 xl:grid-cols-4">
      <StatCard v-for="s in stats" :key="s.label" :stat="s" />
    </div>

    <section class="mt-6 border border-line bg-surface p-6 sm:p-7">
      <h2 class="font-display text-2xl">Pendapatan harian</h2>
      <div class="mt-8"><SalesChart :series="salesSeries" /></div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Produk terlaris</h2>
        <ul class="mt-6 divide-y divide-line border-y border-line">
          <li v-for="p in topProducts" :key="p.name" class="flex items-center justify-between gap-4 py-3.5">
            <span class="text-[0.87rem] text-forest">{{ p.name }}</span>
            <span class="flex-none text-[0.8rem] text-muted">{{ p.sold }} pcs · {{ formatShort(p.revenue) }}</span>
          </li>
        </ul>
      </section>

      <section class="border border-line bg-surface p-6 sm:p-7">
        <h2 class="font-display text-2xl">Sumber pesanan</h2>
        <ul class="mt-6 space-y-6">
          <li v-for="c in channels" :key="c.label">
            <div class="flex items-baseline justify-between">
              <span class="text-[0.87rem] text-forest">{{ c.label }}</span>
              <span class="text-[0.8rem] text-muted">{{ c.share }}%</span>
            </div>
            <div class="mt-2.5 h-1 w-full bg-line"><div class="h-full bg-forest" :style="{ width: `${c.share}%` }" /></div>
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
