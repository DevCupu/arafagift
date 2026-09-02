<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next'
import BulkActionBar from '@/components/admin/BulkActionBar.vue'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  products: { type: Array, required: true },
  categories: { type: Array, required: true },
  suppliers: { type: Array, default: () => [] },
})
const { push } = useToast()

const query = ref('')
const category = ref('semua')
const filter = ref('semua')

const fmt = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0)

const statusOf = (p) =>
  p.status === 'active'
    ? { id: 'active', label: 'Aktif', tone: 'success' }
    : p.status === 'draft'
      ? { id: 'draft', label: 'Draft', tone: 'muted' }
      : { id: 'archived', label: 'Arsip', tone: 'muted' }

const levelPct = (p) => {
  if (p.lowStock <= 0) return p.stock > 0 ? 100 : 0
  return Math.max(0, Math.min(100, (p.stock / p.lowStock) * 100))
}
const levelBarClass = (p) => {
  if (p.stock === 0) return 'bg-danger'
  if (p.stock <= p.lowStock) return 'bg-gold'
  return 'bg-olive'
}

const matches = (p) => {
  const q = query.value.trim().toLowerCase()
  if (!q) return true
  return `${p.name} ${p.sku} ${p.supplier ?? ''}`.toLowerCase().includes(q)
}

const countOf = (id) =>
  props.products.filter((p) =>
    (id === 'semua' || p.status === id) && matches(p),
  ).length

const tabs = computed(() => [
  { id: 'semua', label: 'Semua', count: countOf('semua') },
  { id: 'active', label: 'Aktif', count: countOf('active') },
  { id: 'draft', label: 'Draft', count: countOf('draft') },
])

const rows = computed(() =>
  props.products.filter((p) => {
    const okStatus = filter.value === 'semua' || p.status === filter.value
    const okCat = category.value === 'semua' || p.categorySlug === category.value
    return okStatus && okCat && matches(p)
  }),
)

const draftCount = computed(() => props.products.filter((p) => p.status === 'draft').length)
const outCount = computed(() => props.products.filter((p) => p.stock === 0).length)

const columns = [
  { key: 'product', label: 'Produk', sortKey: 'name' },
  { key: 'category', label: 'Kategori', sortKey: 'category' },
  { key: 'price', label: 'Harga', align: 'right', sortKey: 'price' },
  { key: 'stock', label: 'Stok', align: 'right', sortKey: 'stock' },
  { key: 'status', label: 'Status', sortKey: 'status' },
  { key: 'actions', label: '', align: 'right' },
]

const destroy = (p) => {
  if (!confirm(`Hapus produk "${p.name}"? Tindakan ini tidak bisa dibatalkan.`)) return
  router.delete(`/admin/produk/${p.slug}`, {
    preserveScroll: true,
    onSuccess: () => push(`${p.name} dihapus`, { tone: 'success' }),
    onError: (errors) => push(errors.product ?? 'Gagal menghapus produk', { tone: 'danger' }),
  })
}

const selected = ref([])
const bulkDeleting = ref(false)
const bulkDelete = () => {
  bulkDeleting.value = true
  router.delete('/admin/produk/bulk', {
    data: { ids: selected.value },
    preserveScroll: true,
    onSuccess: () => { push(`${selected.value.length} produk dihapus`, { tone: 'success' }); selected.value = [] },
    onFinish: () => { bulkDeleting.value = false },
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Produk</h1>
        <p class="mt-3 text-[0.85rem] text-muted">
          {{ products.length }} produk · {{ draftCount }} draft · {{ outCount }} stok habis
        </p>
      </div>
      <AppButton to="/admin/produk/baru" size="sm">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Tambah produk
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
        <label class="sr-only" for="prod-q">Cari produk</label>
        <input
          id="prod-q" v-model="query" type="search" placeholder="Cari nama, SKU, atau supplier"
          class="field pl-9" :class="query ? 'pr-9' : ''"
        />
        <button
          v-if="query" type="button" @click="query = ''"
          class="absolute right-3 top-1/2 grid h-5 w-5 -translate-y-1/2 place-items-center text-muted transition hover:text-forest"
          aria-label="Hapus pencarian"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
      <label class="sr-only" for="prod-cat">Kategori</label>
      <select id="prod-cat" v-model="category" class="field w-auto">
        <option value="semua">Semua kategori</option>
        <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
      </select>
    </div>

    <p class="mt-5 text-[0.78rem] text-muted">
      Menampilkan <strong class="text-forest">{{ rows.length }}</strong> dari {{ products.length }} produk
      <template v-if="category !== 'semua'"> dalam kategori <strong class="text-forest">{{ categories.find((c) => c.slug === category)?.name }}</strong></template>
    </p>

    <div class="mt-4">
      <BulkActionBar
        :count="selected.length" label="produk" class="mb-3" :loading="bulkDeleting"
        @clear="selected = []" @delete="bulkDelete"
      />
      <DataTable
        :columns="columns" :rows="rows" selectable v-model:selected="selected"
        :base-empty="products.length === 0" empty-title="Belum ada produk"
        empty-body="Mulai tambahkan produk pertamamu ke katalog."
      >
        <template #cell-product="{ row }">
          <div class="flex items-center gap-3">
            <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory">
              <img v-if="row.image" :src="row.image" :alt="row.name" class="h-full w-full object-cover" />
              <ProductArt v-else :art="row.art" :tone="row.id" />
            </span>
            <span>
              <Link :href="`/admin/produk/${row.slug}`" class="link-underline block font-medium">{{ row.name }}</Link>
              <span class="text-[0.72rem] text-muted">{{ row.sku }}</span>
            </span>
          </div>
        </template>
        <template #cell-category="{ row }"><span class="text-muted">{{ row.category }}</span></template>
        <template #cell-price="{ row }">{{ formatIDR(row.price) }}</template>
        <template #cell-stock="{ row }">
          <div class="ml-auto max-w-[7rem]">
            <p class="flex items-baseline justify-end gap-1.5">
              <span
                class="font-medium tabular-nums"
                :class="row.stock === 0 ? 'text-danger' : row.stock <= row.lowStock ? 'text-gold' : ''"
              >{{ fmt(row.stock) }}</span>
              <span class="text-[0.72rem] text-muted">{{ row.unit }}</span>
            </p>
            <div class="mt-1.5 flex justify-end">
              <div class="h-1 w-full overflow-hidden bg-line/70" :title="`${Math.round(levelPct(row))}% dari batas menipis`">
                <div class="h-full transition-all" :class="levelBarClass(row)" :style="{ width: `${levelPct(row)}%` }" />
              </div>
            </div>
          </div>
        </template>
        <template #cell-status="{ row }">
          <StatusPill :label="statusOf(row).label" :tone="statusOf(row).tone" />
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1 opacity-60 transition group-hover:opacity-100">
            <Link :href="`/admin/produk/${row.slug}`" class="grid h-8 w-8 place-items-center text-muted transition hover:text-forest" aria-label="Ubah produk">
              <Pencil class="h-3.5 w-3.5" />
            </Link>
            <button type="button" class="grid h-8 w-8 place-items-center text-muted transition hover:text-danger" aria-label="Hapus produk" @click="destroy(row)">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
        <template #empty-action>
          <AppButton to="/admin/produk/baru" size="sm">
            <template #icon><Plus class="h-3.5 w-3.5" /></template>
            Tambah produk
          </AppButton>
        </template>
      </DataTable>
    </div>
  </div>
</template>