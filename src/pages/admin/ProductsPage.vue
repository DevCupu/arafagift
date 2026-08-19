<script setup>
import { computed, ref } from 'vue'
import { Copy, Pencil, Plus, Search, Trash2 } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { categories, products } from '@/data/catalog'
import { formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const query = ref('')
const category = ref('semua')

const columns = [
  { key: 'product', label: 'Produk' },
  { key: 'category', label: 'Kategori' },
  { key: 'price', label: 'Harga', align: 'right' },
  { key: 'stock', label: 'Stok', align: 'right' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' },
]

const rows = computed(() =>
  products.filter((p) => {
    const okCat = category.value === 'semua' || p.categorySlug === category.value
    const q = query.value.trim().toLowerCase()
    return okCat && (!q || `${p.name} ${p.sku}`.toLowerCase().includes(q))
  }),
)
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Produk</h1>
        <p class="mt-3 text-[0.85rem] text-muted">{{ products.length }} produk · 1 draft · 1 stok habis</p>
      </div>
      <AppButton to="/admin/produk/baru" size="sm">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Tambah produk
      </AppButton>
    </header>

    <div class="mt-8 flex flex-wrap items-center gap-3">
      <div class="relative w-full max-w-sm">
        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" :stroke-width="1.5" />
        <label class="sr-only" for="prod-q">Cari produk</label>
        <input id="prod-q" v-model="query" class="field pl-9" placeholder="Cari nama produk atau SKU" />
      </div>
      <label class="sr-only" for="prod-cat">Kategori</label>
      <select id="prod-cat" v-model="category" class="field w-auto">
        <option value="semua">Semua kategori</option>
        <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
      </select>
    </div>

    <div class="mt-5">
      <DataTable :columns="columns" :rows="rows">
        <template #cell-product="{ row }">
          <div class="flex items-center gap-3">
            <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="row.art" :tone="row.id" /></span>
            <span>
              <router-link :to="`/admin/produk/${row.slug}`" class="link-underline block font-medium">{{ row.name }}</router-link>
              <span class="text-[0.72rem] text-muted">{{ row.sku }}</span>
            </span>
          </div>
        </template>
        <template #cell-category="{ row }"><span class="text-muted">{{ row.category }}</span></template>
        <template #cell-price="{ row }">{{ formatIDR(row.price) }}</template>
        <template #cell-stock="{ row }">
          <span :class="row.stock === 0 ? 'text-danger' : row.stock <= row.lowStock ? 'text-gold' : ''">{{ row.stock }}</span>
        </template>
        <template #cell-status="{ row }">
          <StatusPill
            :label="row.status === 'active' ? 'Aktif' : 'Draft'"
            :tone="row.status === 'active' ? 'success' : 'muted'"
          />
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1 opacity-60 transition group-hover:opacity-100">
            <router-link :to="`/admin/produk/${row.slug}`" class="grid h-8 w-8 place-items-center text-muted transition hover:text-forest" aria-label="Ubah">
              <Pencil class="h-3.5 w-3.5" />
            </router-link>
            <button class="grid h-8 w-8 place-items-center text-muted transition hover:text-forest" aria-label="Duplikat" @click="push(`${row.name} diduplikasi sebagai draft`)">
              <Copy class="h-3.5 w-3.5" />
            </button>
            <button class="grid h-8 w-8 place-items-center text-muted transition hover:text-danger" aria-label="Hapus" @click="push(`${row.name} dipindahkan ke arsip`)">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
      </DataTable>
    </div>
  </div>
</template>
