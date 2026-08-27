<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import SalesChart from '@/components/admin/SalesChart.vue'
import StatCard from '@/components/admin/StatCard.vue'
import { formatShort } from '@/composables/useFormat'

defineProps({
  stats: { type: Array, required: true },
  salesSeries: { type: Array, required: true },
  topProducts: { type: Array, required: true },
  channels: { type: Array, required: true },
})
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
