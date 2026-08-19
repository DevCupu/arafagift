<script setup>
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { orders, orderTotal } from '@/data/admin'
import { formatDate, formatIDR } from '@/composables/useFormat'

const mine = orders.slice(0, 4)
</script>

<template>
  <AccountShell title="Pesanan saya" sub="Semua pesanan Anda, terbaru di atas.">
    <ul v-if="mine.length" class="space-y-4">
      <li v-for="o in mine" :key="o.id" class="border border-line bg-surface">
        <div class="flex flex-wrap items-center justify-between gap-4 border-b border-line px-6 py-4">
          <div>
            <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">{{ formatDate(o.date) }}</p>
            <p class="mt-1 font-display text-xl text-forest">{{ o.id }}</p>
          </div>
          <div class="flex items-center gap-5">
            <StatusPill :status="o.status" />
            <AppButton :to="`/akun/pesanan/${o.id}`" variant="quiet" size="sm">Lihat detail</AppButton>
          </div>
        </div>
        <div class="flex flex-wrap items-center gap-5 px-6 py-5">
          <div class="flex -space-x-3">
            <span v-for="(it, i) in o.items" :key="i" class="arch h-14 w-11 overflow-hidden border border-line bg-ivory">
              <ProductArt :art="it.art" :tone="i" />
            </span>
          </div>
          <p class="flex-1 text-[0.85rem] text-muted">
            {{ o.items.length }} jenis produk · {{ o.items.reduce((n, i) => n + i.qty, 0) }} pcs
          </p>
          <p class="font-display text-xl text-forest">{{ formatIDR(orderTotal(o)) }}</p>
        </div>
      </li>
    </ul>
    <EmptyState v-else title="Belum ada pesanan" body="Pesanan pertama Anda akan muncul di sini.">
      <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
    </EmptyState>
  </AccountShell>
</template>
