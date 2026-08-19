<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { customers, orders, orderTotal } from '@/data/admin'
import { formatDate, formatDateTime, formatIDR } from '@/composables/useFormat'

const props = defineProps({ id: { type: [String, Number], required: true } })
const customer = computed(() => customers.find((c) => c.id === Number(props.id)))
const theirOrders = computed(() => orders.filter((o) => o.customer === customer.value?.name))

const activity = [
  ['Membuka email “Koleksi musim haji”', '18 Ags 2026, 08.20'],
  ['Menambahkan Arafah Premium Box ke keranjang', '18 Ags 2026, 09.02'],
  ['Menyelesaikan pembayaran', '18 Ags 2026, 09.12'],
]
</script>

<template>
  <div v-if="customer">
    <Link href="/admin/pelanggan" class="inline-flex items-center gap-2 text-[0.8rem] text-muted transition hover:text-forest">
      <ArrowLeft class="h-3.5 w-3.5" /> Semua pelanggan
    </Link>

    <header class="mt-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">{{ customer.name }}</h1>
        <p class="mt-3 text-[0.85rem] text-muted">{{ customer.email }} · {{ customer.phone }}</p>
      </div>
      <StatusPill :label="customer.tag" tone="info" />
    </header>

    <div class="mt-8 grid gap-px overflow-hidden border border-line bg-line sm:grid-cols-3">
      <div class="bg-surface px-6 py-5">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Total belanja</p>
        <p class="mt-3 font-display text-[1.9rem] leading-none text-forest">{{ formatIDR(customer.spent) }}</p>
      </div>
      <div class="bg-surface px-6 py-5">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Jumlah pesanan</p>
        <p class="mt-3 font-display text-[1.9rem] leading-none text-forest">{{ customer.orders }}</p>
      </div>
      <div class="bg-surface px-6 py-5">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Bergabung</p>
        <p class="mt-3 font-display text-[1.9rem] leading-none text-forest">{{ formatDate(customer.joined) }}</p>
      </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <section class="border border-line bg-surface">
        <h2 class="border-b border-line px-6 py-5 font-display text-2xl">Riwayat pesanan</h2>
        <ul v-if="theirOrders.length" class="divide-y divide-line">
          <li v-for="o in theirOrders" :key="o.id">
            <Link :href="`/admin/pesanan/${o.id}`" class="flex items-center gap-4 px-6 py-4 transition hover:bg-ivory/70">
              <span class="flex-1">
                <span class="block text-[0.88rem] text-forest">{{ o.id }}</span>
                <span class="text-[0.72rem] text-muted">{{ formatDateTime(o.date) }}</span>
              </span>
              <StatusPill :status="o.status" />
              <span class="w-28 text-right text-[0.88rem] text-forest">{{ formatIDR(orderTotal(o)) }}</span>
            </Link>
          </li>
        </ul>
        <p v-else class="px-6 py-10 text-center text-[0.85rem] text-muted">Belum ada pesanan tercatat.</p>
      </section>

      <div class="space-y-6">
        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Alamat tersimpan</h2>
          <p class="mt-4 text-[0.85rem] leading-relaxed text-muted">
            {{ theirOrders[0]?.address ?? 'Belum ada alamat tersimpan.' }}
          </p>
        </section>
        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Aktivitas</h2>
          <ol class="mt-5 border-l border-line pl-5">
            <li v-for="([text, time]) in activity" :key="text" class="relative pb-5 last:pb-0">
              <span class="absolute -left-[1.42rem] top-1.5 h-2 w-2 rounded-full bg-gold" />
              <p class="text-[0.85rem] text-forest">{{ text }}</p>
              <p class="mt-0.5 text-[0.72rem] text-muted">{{ time }}</p>
            </li>
          </ol>
        </section>
      </div>
    </div>
  </div>

  <EmptyState v-else title="Pelanggan tidak ditemukan" body="Data pelanggan ini mungkin sudah dihapus.">
    <AppButton to="/admin/pelanggan">Ke daftar pelanggan</AppButton>
  </EmptyState>
</template>
