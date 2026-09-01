<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Download, Search, X } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { orderStatuses, orderTotal } from '@/data/admin'
import { formatDateTime, formatIDR } from '@/composables/useFormat'

const props = defineProps({ orders: { type: Array, required: true } })
const query = ref('')
const filter = ref('semua')

const columns = [
  { key: 'id', label: 'Pesanan', sortKey: 'id' },
  { key: 'customer', label: 'Pelanggan', sortKey: 'customer' },
  { key: 'date', label: 'Tanggal', sortKey: 'date' },
  { key: 'items', label: 'Item', sortFn: (o) => o.items.reduce((n, i) => n + i.qty, 0) },
  { key: 'total', label: 'Total', align: 'right', sortFn: (o) => orderTotal(o) },
  { key: 'payment', label: 'Pembayaran', sortKey: 'payment' },
  { key: 'status', label: 'Status', sortKey: 'status' },
]

const rows = computed(() =>
  props.orders.filter((o) => {
    const okStatus = filter.value === 'semua' || o.status === filter.value
    const q = query.value.trim().toLowerCase()
    const okQuery = !q || `${o.id} ${o.customer} ${o.email}`.toLowerCase().includes(q)
    return okStatus && okQuery
  }),
)

const pendingCount = computed(() => props.orders.filter((o) => o.status === 'pending').length)

const countOf = (id) =>
  props.orders.filter((o) => {
    const q = query.value.trim().toLowerCase()
    const okQuery = !q || `${o.id} ${o.customer} ${o.email}`.toLowerCase().includes(q)
    return (id === 'semua' || o.status === id) && okQuery
  }).length

const tabs = computed(() => [
  { id: 'semua', label: 'Semua', count: countOf('semua') },
  ...orderStatuses.map((s) => ({ id: s.id, label: s.label, count: countOf(s.id) })),
])
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Pesanan</h1>
        <p class="mt-3 text-[0.85rem] text-muted">{{ orders.length }} pesanan · {{ pendingCount }} menunggu pembayaran</p>
      </div>
      <AppButton href="/admin/pesanan/ekspor" variant="outline" size="sm">
        <template #icon><Download class="h-3.5 w-3.5" /></template>
        Ekspor CSV
      </AppButton>
    </header>

    <div class="no-scrollbar mt-8 flex gap-6 overflow-x-auto border-b border-line">
      <button
        v-for="t in tabs" :key="t.id"
        type="button"
        class="relative whitespace-nowrap pb-3 text-[0.83rem] transition"
        :class="filter === t.id ? 'text-forest' : 'text-muted hover:text-forest'"
        @click="filter = t.id"
      >
        {{ t.label }} <span class="ml-1 text-[0.72rem]" :class="filter === t.id ? 'text-gold' : 'text-muted/70'">({{ t.count }})</span>
        <span v-if="filter === t.id" class="absolute inset-x-0 -bottom-px h-px bg-gold" />
      </button>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-3">
      <div class="relative w-full max-w-sm">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" :stroke-width="1.5" />
        <label class="sr-only" for="order-q">Cari pesanan</label>
        <input
          id="order-q" v-model="query" type="search" class="field pl-9" :class="query ? 'pr-9' : ''"
          placeholder="Cari nomor pesanan atau nama pelanggan"
        />
        <button
          v-if="query" type="button" @click="query = ''"
          class="absolute right-3 top-1/2 grid h-5 w-5 -translate-y-1/2 place-items-center text-muted transition hover:text-forest"
          aria-label="Hapus pencarian"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>

    <p class="mt-5 text-[0.78rem] text-muted">
      Menampilkan <strong class="text-forest">{{ rows.length }}</strong> dari {{ orders.length }} pesanan
    </p>

    <div class="mt-4">
      <DataTable :columns="columns" :rows="rows">
        <template #cell-id="{ row }">
          <Link :href="`/admin/pesanan/${row.id}`" class="link-underline font-medium">{{ row.id }}</Link>
        </template>
        <template #cell-customer="{ row }">
          <span class="block">{{ row.customer }}</span>
          <span class="text-[0.72rem] text-muted">{{ row.email }}</span>
        </template>
        <template #cell-date="{ row }"><span class="text-muted">{{ formatDateTime(row.date) }}</span></template>
        <template #cell-items="{ row }">{{ row.items.reduce((n, i) => n + i.qty, 0) }} pcs</template>
        <template #cell-total="{ row }"><span class="font-medium">{{ formatIDR(orderTotal(row)) }}</span></template>
        <template #cell-payment="{ row }"><span class="text-muted">{{ row.payment }}</span></template>
        <template #cell-status="{ row }"><StatusPill :status="row.status" /></template>
      </DataTable>
    </div>
  </div>
</template>
