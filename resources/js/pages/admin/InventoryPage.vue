<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import { products } from '@/data/catalog'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const filter = ref('semua')
const stocks = ref(Object.fromEntries(products.map((p) => [p.id, p.stock])))

const state = (p) => {
  const s = stocks.value[p.id]
  if (s === 0) return { id: 'out', label: 'Stok habis', tone: 'danger' }
  if (s <= p.lowStock) return { id: 'low', label: 'Menipis', tone: 'warn' }
  return { id: 'in', label: 'Tersedia', tone: 'success' }
}

const columns = [
  { key: 'name', label: 'Produk' },
  { key: 'sku', label: 'SKU' },
  { key: 'stock', label: 'Stok', align: 'right' },
  { key: 'lowStock', label: 'Batas menipis', align: 'right' },
  { key: 'state', label: 'Status' },
]

const tabs = [
  { id: 'semua', label: 'Semua' },
  { id: 'low', label: 'Menipis' },
  { id: 'out', label: 'Habis' },
]

const rows = computed(() =>
  products.filter((p) => filter.value === 'semua' || state(p).id === filter.value),
)

const update = (p, value) => {
  stocks.value[p.id] = Math.max(0, Number(value) || 0)
  push(`Stok ${p.name} diperbarui jadi ${stocks.value[p.id]}`)
}
</script>

<template>
  <div>
    <header>
      <h1 class="text-[2.1rem] leading-none">Inventori</h1>
      <p class="mt-3 text-[0.85rem] text-muted">Ubah angka stok langsung di tabel. Perubahan langsung terpakai di storefront.</p>
    </header>

    <div class="mt-8 flex gap-6 border-b border-line">
      <button
        v-for="t in tabs" :key="t.id"
        class="relative pb-3 text-[0.83rem] transition"
        :class="filter === t.id ? 'text-forest' : 'text-muted hover:text-forest'"
        @click="filter = t.id"
      >
        {{ t.label }}
        <span v-if="filter === t.id" class="absolute inset-x-0 -bottom-px h-px bg-gold" />
      </button>
    </div>

    <div class="mt-6">
      <DataTable :columns="columns" :rows="rows">
        <template #cell-name="{ row }">
          <span class="font-medium">{{ row.name }}</span>
          <span class="block text-[0.72rem] text-muted">{{ row.category }}</span>
        </template>
        <template #cell-sku="{ row }"><span class="text-muted">{{ row.sku }}</span></template>
        <template #cell-stock="{ row }">
          <label class="sr-only" :for="`stock-${row.id}`">Stok {{ row.name }}</label>
          <input
            :id="`stock-${row.id}`" :value="stocks[row.id]" inputmode="numeric"
            class="w-20 border border-line bg-surface px-2 py-1.5 text-right text-[0.85rem] focus:border-olive focus:outline-none"
            @change="update(row, $event.target.value)"
          />
        </template>
        <template #cell-lowStock="{ row }"><span class="text-muted">{{ row.lowStock }}</span></template>
        <template #cell-state="{ row }"><StatusPill :label="state(row).label" :tone="state(row).tone" /></template>
      </DataTable>
    </div>
  </div>
</template>
