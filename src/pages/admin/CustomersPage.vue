<script setup>
import { computed, ref } from 'vue'
import { Search } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import { customers } from '@/data/admin'
import { formatDate, formatIDR } from '@/composables/useFormat'

const query = ref('')
const columns = [
  { key: 'name', label: 'Nama' },
  { key: 'phone', label: 'Telepon' },
  { key: 'city', label: 'Kota' },
  { key: 'orders', label: 'Pesanan', align: 'right' },
  { key: 'spent', label: 'Total belanja', align: 'right' },
  { key: 'lastOrder', label: 'Pesanan terakhir' },
]
const rows = computed(() => {
  const q = query.value.trim().toLowerCase()
  return customers.filter((c) => !q || `${c.name} ${c.email} ${c.city}`.toLowerCase().includes(q))
})
</script>

<template>
  <div>
    <header>
      <h1 class="text-[2.1rem] leading-none">Pelanggan</h1>
      <p class="mt-3 text-[0.85rem] text-muted">{{ customers.length }} pelanggan terdaftar · 2 akun travel</p>
    </header>

    <div class="relative mt-8 w-full max-w-sm">
      <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" :stroke-width="1.5" />
      <label class="sr-only" for="cust-q">Cari pelanggan</label>
      <input id="cust-q" v-model="query" class="field pl-9" placeholder="Cari nama, email, atau kota" />
    </div>

    <div class="mt-5">
      <DataTable :columns="columns" :rows="rows">
        <template #cell-name="{ row }">
          <router-link :to="`/admin/pelanggan/${row.id}`" class="link-underline block font-medium">{{ row.name }}</router-link>
          <span class="text-[0.72rem] text-muted">{{ row.email }}</span>
        </template>
        <template #cell-phone="{ row }"><span class="text-muted">{{ row.phone }}</span></template>
        <template #cell-city="{ row }"><span class="text-muted">{{ row.city }}</span></template>
        <template #cell-spent="{ row }"><span class="font-medium">{{ formatIDR(row.spent) }}</span></template>
        <template #cell-lastOrder="{ row }"><span class="text-muted">{{ formatDate(row.lastOrder) }}</span></template>
      </DataTable>
    </div>
  </div>
</template>
