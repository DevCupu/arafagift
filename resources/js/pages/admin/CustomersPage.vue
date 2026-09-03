<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Search, X } from 'lucide-vue-next'
import BulkActionBar from '@/components/admin/BulkActionBar.vue'
import DataTable from '@/components/admin/DataTable.vue'
import { formatDate, formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({ customers: { type: Array, required: true } })
const { push } = useToast()
const query = ref('')
const columns = [
  { key: 'name', label: 'Nama', sortKey: 'name' },
  { key: 'phone', label: 'Telepon', sortKey: 'phone' },
  { key: 'city', label: 'Kota', sortKey: 'city' },
  { key: 'orders', label: 'Pesanan', align: 'right', sortKey: 'orders' },
  { key: 'spent', label: 'Total belanja', align: 'right', sortKey: 'spent' },
  { key: 'lastOrder', label: 'Pesanan terakhir', sortKey: 'lastOrder' },
]
const rows = computed(() => {
  const q = query.value.trim().toLowerCase()
  return props.customers.filter((c) => !q || `${c.name} ${c.email} ${c.city}`.toLowerCase().includes(q))
})

const selected = ref([])
const bulkDeleting = ref(false)
const bulkDelete = () => {
  bulkDeleting.value = true
  router.delete('/admin/pelanggan/bulk', {
    data: { ids: selected.value },
    preserveScroll: true,
    onSuccess: () => { push(`${selected.value.length} pelanggan dihapus`, { tone: 'success' }); selected.value = [] },
    onError: (errors) => push(errors.customer ?? 'Gagal menghapus sebagian pelanggan', { tone: 'danger' }),
    onFinish: () => { bulkDeleting.value = false },
  })
}
</script>

<template>
  <div>
    <header>
      <h1 class="text-[2.1rem] leading-none">Pelanggan</h1>
      <p class="mt-3 text-[0.85rem] text-muted">{{ customers.length }} pelanggan terdaftar</p>
    </header>

    <div class="relative mt-8 w-full max-w-sm">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" :stroke-width="1.5" />
      <label class="sr-only" for="cust-q">Cari pelanggan</label>
      <input
        id="cust-q" v-model="query" type="search" class="field pl-9" :class="query ? 'pr-9' : ''"
        placeholder="Cari nama, email, atau kota"
      />
      <button
        v-if="query" type="button" @click="query = ''"
        class="absolute right-3 top-1/2 grid h-5 w-5 -translate-y-1/2 place-items-center text-muted transition hover:text-forest"
        aria-label="Hapus pencarian"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <p class="mt-5 text-[0.78rem] text-muted">
      Menampilkan <strong class="text-forest">{{ rows.length }}</strong> dari {{ customers.length }} pelanggan
    </p>

    <div class="mt-4">
      <BulkActionBar
        :count="selected.length" label="pelanggan" class="mb-3" :loading="bulkDeleting"
        @clear="selected = []" @delete="bulkDelete"
      />
      <DataTable
        :columns="columns" :rows="rows" selectable v-model:selected="selected"
        :base-empty="customers.length === 0" empty-title="Belum ada pelanggan"
        empty-body="Pelanggan yang sudah melakukan pembelian akan muncul di sini."
      >
        <template #cell-name="{ row }">
          <Link :href="`/admin/pelanggan/${row.id}`" class="link-underline block font-medium">{{ row.name }}</Link>
          <span class="text-[0.72rem] text-muted">{{ row.email }}</span>
        </template>
        <template #cell-phone="{ row }"><span class="text-muted">{{ row.phone }}</span></template>
        <template #cell-city="{ row }"><span class="text-muted">{{ row.city }}</span></template>
        <template #cell-spent="{ row }"><span class="font-medium">{{ formatIDR(row.spent) }}</span></template>
        <template #cell-lastOrder="{ row }"><span class="text-muted">{{ row.lastOrder ? formatDate(row.lastOrder) : '—' }}</span></template>
      </DataTable>
    </div>
  </div>
</template>
