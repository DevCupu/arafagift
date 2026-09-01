<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  AlertTriangle, Boxes, Download, Filter, History, Layers,
  PackageMinus, PackagePlus, PackageX, Scale, Search, Wallet, X,
} from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  products: { type: Array, required: true },
  suppliers: { type: Array, default: () => [] },
  summary: { type: Object, required: true },
})
const { push } = useToast()

const fmt = (n) => new Intl.NumberFormat('id-ID').format(n ?? 0)

const state = (p) => {
  if (p.stock === 0) return { id: 'out', label: 'Stok habis', tone: 'danger' }
  if (p.stock <= p.lowStock) return { id: 'low', label: 'Menipis', tone: 'warn' }
  return { id: 'in', label: 'Tersedia', tone: 'success' }
}

const filter = ref('semua')
const search = ref('')
const sortBy = ref('nama')

const tabs = computed(() => [
  { id: 'semua', label: 'Semua', count: countOf('semua') },
  { id: 'low', label: 'Menipis', count: countOf('low') },
  { id: 'out', label: 'Habis', count: countOf('out') },
])

const sortOptions = [
  { id: 'nama', label: 'Nama A–Z' },
  { id: 'stok-asc', label: 'Stok terkecil' },
  { id: 'stok-desc', label: 'Stok terbesar' },
  { id: 'nilai-desc', label: 'Nilai stok tertinggi' },
]

const matches = (p) => {
  const q = search.value.trim().toLowerCase()
  if (!q) return true
  return [p.name, p.sku, p.category, p.storageLocation, p.supplier]
    .some((v) => v && String(v).toLowerCase().includes(q))
}

function countOf(filterId) {
  return props.products.filter((p) =>
    (filterId === 'semua' || state(p).id === filterId) && matches(p),
  ).length
}

const rows = computed(() => {
  const filtered = props.products.filter((p) =>
    (filter.value === 'semua' || state(p).id === filter.value) && matches(p),
  )
  const sorted = [...filtered]
  sorted.sort((a, b) => {
    switch (sortBy.value) {
      case 'stok-asc': return a.stock - b.stock
      case 'stok-desc': return b.stock - a.stock
      case 'nilai-desc': return (b.cost ?? 0) * b.stock - (a.cost ?? 0) * a.stock
      default: return a.name.localeCompare(b.name)
    }
  })
  return sorted
})

const thresholds = ref(Object.fromEntries(props.products.map((p) => [p.id, p.lowStock])))

const saveThreshold = (p, value) => {
  const threshold = Math.max(0, Number(value) || 0)
  thresholds.value[p.id] = threshold
  router.patch(`/admin/inventori/${p.id}/ambang`, { low_stock_threshold: threshold }, {
    preserveScroll: true,
    onSuccess: () => push(`Batas menipis ${p.name} jadi ${threshold}`, { tone: 'success' }),
  })
}

// Indikator stok relatif terhadap batas menipis
const levelPct = (p) => {
  if (p.lowStock <= 0) return p.stock > 0 ? 100 : 0
  return Math.max(0, Math.min(100, (p.stock / p.lowStock) * 100))
}
const levelBarClass = (p) => {
  if (p.stock === 0) return 'bg-danger'
  if (p.stock <= p.lowStock) return 'bg-gold'
  return 'bg-olive'
}

// ── Modal pencatatan gerakan ──

const IN_TYPES = [
  { id: 'purchase', label: 'Pembelian supplier' },
  { id: 'customer_return', label: 'Retur pelanggan' },
  { id: 'manual_in', label: 'Tambah manual' },
]
const OUT_TYPES = [
  { id: 'internal_use', label: 'Pemakaian internal' },
  { id: 'damage', label: 'Barang rusak' },
  { id: 'loss', label: 'Barang hilang' },
  { id: 'supplier_return', label: 'Retur ke supplier' },
]
const DIRECTION = {
  purchase: 1, customer_return: 1, manual_in: 1,
  internal_use: -1, damage: -1, loss: -1, supplier_return: -1,
}

const showForm = ref(false)
const target = ref(null)
const form = useForm({ type: 'purchase', qty: 1, delta: 0, supplier_id: '', note: '' })

const startRecord = (p, type = 'purchase') => {
  target.value = p
  form.type = type
  form.qty = 1
  form.delta = 0
  form.supplier_id = ''
  form.note = ''
  form.clearErrors()
  showForm.value = true
}

const previewStock = computed(() => {
  if (!target.value) return 0
  const dir = DIRECTION[form.type] ?? 0
  const delta = form.type === 'adjustment' ? Number(form.delta || 0) : dir * Number(form.qty || 0)
  return target.value.stock + delta
})

const submitMovement = () => {
  const payload = form.type === 'adjustment'
    ? { type: form.type, delta: form.delta, note: form.note }
    : { type: form.type, qty: form.qty, supplier_id: form.supplier_id || null, note: form.note }

  form.transform(() => payload).post(`/admin/inventori/${target.value.id}/gerakan`, {
    preserveScroll: true,
    onSuccess: () => {
      push(`Gerakan stok ${target.value.name} dicatat`, { tone: 'success' })
      showForm.value = false
    },
  })
}

// ── Drawer riwayat mutasi ──

const showHistory = ref(false)
const historyLoading = ref(false)
const historyProduct = ref(null)
const movements = ref([])

const fmtDate = (iso) => new Date(iso).toLocaleString('id-ID', {
  day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
})

const openHistory = async (p) => {
  historyProduct.value = p
  movements.value = []
  showHistory.value = true
  historyLoading.value = true
  try {
    const res = await fetch(`/admin/inventori/${p.id}/riwayat`, { headers: { Accept: 'application/json' } })
    const data = await res.json()
    movements.value = data.movements ?? []
  } finally {
    historyLoading.value = false
  }
}

const columns = [
  { key: 'name', label: 'Produk', sortKey: 'name' },
  { key: 'sku', label: 'SKU / Satuan', sortKey: 'sku' },
  { key: 'location', label: 'Lokasi', sortKey: 'storageLocation' },
  { key: 'stock', label: 'Stok', align: 'right', sortKey: 'stock' },
  { key: 'lowStock', label: 'Batas menipis', align: 'right', sortKey: 'lowStock' },
  { key: 'value', label: 'Nilai stok', align: 'right', sortFn: (r) => (r.cost ?? 0) * r.stock },
  { key: 'state', label: 'Status' },
  { key: 'actions', label: '' },
]

const summaryCards = computed(() => [
  { label: 'Total SKU', value: fmt(props.summary.skuCount), hint: 'semua produk terdaftar', icon: Boxes, fid: 'semua' },
  { label: 'Total unit stok', value: fmt(props.summary.unitCount), hint: 'jumlah unit seluruh gudang', icon: Layers, fid: null },
  { label: 'Nilai stok (HPP)', value: `Rp ${fmt(props.summary.stockValue)}`, hint: 'harga beli × stok', icon: Wallet, fid: null },
  { label: 'Stok menipis', value: fmt(props.summary.lowCount), hint: 'klik untuk filter', icon: AlertTriangle, fid: 'low' },
  { label: 'Stok habis', value: fmt(props.summary.outCount), hint: 'klik untuk filter', icon: PackageX, fid: 'out' },
])
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Inventori</h1>
        <p class="mt-3 text-[0.85rem] text-muted">
          Semua perubahan stok tercatat di buku mutasi — lengkap dengan petugas, dokumen, dan referensinya.
        </p>
      </div>
      <AppButton href="/admin/inventori/ekspor" variant="outline" size="sm">
        <template #icon><Download class="h-3.5 w-3.5" /></template>
        Ekspor CSV
      </AppButton>
    </header>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
      <button
        v-for="card in summaryCards"
        :key="card.label"
        type="button"
        :disabled="!card.fid"
        class="relative border px-5 py-4 text-left transition sm:py-5"
        :class="card.fid
          ? (filter === card.fid ? 'border-gold bg-gold/10' : 'border-line bg-surface hover:border-olive/50 hover:shadow-soft')
          : 'cursor-default border-line bg-surface'"
        @click="filter = card.fid"
      >
        <div class="flex items-start justify-between gap-3">
          <p class="text-[0.68rem] font-medium uppercase tracking-[0.14em] text-muted">{{ card.label }}</p>
          <span
            class="grid h-8 w-8 flex-none place-items-center transition"
            :class="filter === card.fid && card.fid ? 'bg-gold/20 text-gold' : 'text-muted/50'"
          >
            <component :is="card.icon" class="h-4 w-4" :stroke-width="1.5" />
          </span>
        </div>
        <p class="mt-3 font-display text-[1.55rem] leading-none text-forest">{{ card.value }}</p>
        <p class="mt-2 flex items-center gap-1 text-[0.72rem]" :class="card.fid ? 'text-gold' : 'text-muted'">
          <Filter v-if="card.fid" class="h-3 w-3" :stroke-width="1.5" />
          {{ card.hint }}
        </p>
      </button>
    </section>

    <div class="mt-8 flex flex-wrap items-center justify-between gap-4">
      <div class="flex gap-6 border-b border-line">
<button
            v-for="t in tabs" :key="t.id"
            type="button"
            class="relative pb-3 text-[0.83rem] transition"
            :class="filter === t.id ? 'text-forest' : 'text-muted hover:text-forest'"
            @click="filter = t.id"
          >
            {{ t.label }} <span class="ml-1 text-[0.72rem]" :class="filter === t.id ? 'text-gold' : 'text-muted/70'">({{ t.count }})</span>
            <span v-if="filter === t.id" class="absolute inset-x-0 -bottom-px h-px bg-gold" />
          </button>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <label class="relative">
          <span class="sr-only">Cari barang</span>
          <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-muted" />
          <input
            v-model="search" type="search" placeholder="Cari nama, SKU, lokasi…"
            class="w-56 border border-line bg-surface py-2 pl-9 text-[0.82rem] focus:border-olive focus:outline-none"
            :class="search ? 'pr-8' : 'pr-3'"
          />
          <button
            v-if="search" type="button" @click="search = ''"
            class="absolute right-2 top-1/2 grid h-5 w-5 -translate-y-1/2 place-items-center text-muted transition hover:text-forest"
            aria-label="Hapus pencarian"
          >
            <X class="h-3.5 w-3.5" />
          </button>
        </label>
        <select v-model="sortBy" class="field w-auto py-2 text-[0.82rem]">
          <option v-for="o in sortOptions" :key="o.id" :value="o.id">{{ o.label }}</option>
        </select>
      </div>
    </div>

    <p class="mt-5 text-[0.78rem] text-muted">
      Menampilkan <strong class="text-forest">{{ rows.length }}</strong> dari {{ props.products.length }} produk
      <template v-if="filter !== 'semua'"> dengan status <strong class="text-forest">{{ tabs.find((t) => t.id === filter).label }}</strong></template>
    </p>

    <div class="mt-4">
      <DataTable :columns="columns" :rows="rows">
        <template #cell-name="{ row }">
          <span class="font-medium">{{ row.name }}</span>
          <span class="block text-[0.72rem] text-muted">{{ row.category }}</span>
        </template>
        <template #cell-sku="{ row }">
          <span class="text-muted">{{ row.sku }}</span>
          <span class="block text-[0.72rem] text-muted">{{ row.unit }}</span>
        </template>
        <template #cell-location="{ row }">
          <span class="text-[0.8rem]" :class="row.storageLocation ? 'text-muted' : 'text-muted/50 italic'">
            {{ row.storageLocation ?? 'belum diatur' }}
          </span>
        </template>
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
        <template #cell-lowStock="{ row }">
          <input
            :value="thresholds[row.id]" inputmode="numeric" :aria-label="`Batas menipis ${row.name}`"
            title="Ubah batas menipis, simpan otomatis"
            class="w-16 border border-line bg-surface px-2 py-1 text-right text-[0.82rem] tabular-nums focus:border-olive focus:outline-none"
            @change="saveThreshold(row, $event.target.value)"
          />
        </template>
        <template #cell-value="{ row }">
          <span class="tabular-nums text-muted">{{ row.cost !== null && row.cost !== undefined ? `Rp ${fmt(row.cost * row.stock)}` : '–' }}</span>
        </template>
        <template #cell-state="{ row }"><StatusPill :label="state(row).label" :tone="state(row).tone" /></template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-2.5">
            <div class="flex overflow-hidden border border-line" role="group" :aria-label="`Aksi cepat ${row.name}`">
              <button
                type="button"
                class="grid h-7 w-8 place-items-center border-r border-line text-muted transition hover:bg-olive hover:text-ivory"
                :title="`Tambah stok — restock ${row.name}`"
                aria-label="Catat stok masuk"
                @click="startRecord(row, 'purchase')"
              >
                <PackagePlus class="h-3.5 w-3.5" />
              </button>
              <button
                type="button"
                class="grid h-7 w-8 place-items-center text-muted transition hover:bg-gold hover:text-forest-deep"
                :title="`Kurangi stok ${row.name}`"
                aria-label="Catat stok keluar"
                @click="startRecord(row, 'internal_use')"
              >
                <PackageMinus class="h-3.5 w-3.5" />
              </button>
            </div>
            <button
              type="button"
              class="inline-flex items-center gap-1.5 text-[0.76rem] text-muted underline underline-offset-4 hover:text-forest"
              @click="openHistory(row)"
            >
              <History class="h-3.5 w-3.5" /> Riwayat
            </button>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Modal pencatatan gerakan -->
    <div v-if="showForm && target" class="fixed inset-0 z-[90] flex items-end justify-center bg-forest-deep/50 p-4 backdrop-blur-sm sm:items-center" @click.self="showForm = false">
      <form class="w-full max-w-lg border border-line bg-surface p-6 shadow-xl" @submit.prevent="submitMovement">
        <header>
          <p class="text-[0.68rem] uppercase tracking-[0.14em] text-gold">Catat gerakan stok</p>
          <h2 class="mt-2 font-display text-2xl leading-none text-forest">{{ target.name }}</h2>
          <p class="mt-2 text-[0.78rem] text-muted">
            Stok saat ini <strong class="text-forest">{{ fmt(target.stock) }} {{ target.unit }}</strong> · {{ target.sku }}
          </p>
        </header>

        <div class="mt-5 space-y-4">
          <div>
            <label class="field-label" for="m-type">Jenis gerakan</label>
            <select id="m-type" v-model="form.type" class="field">
              <optgroup label="Stok masuk">
                <option v-for="t in IN_TYPES" :key="t.id" :value="t.id">{{ t.label }}</option>
              </optgroup>
              <optgroup label="Stok keluar">
                <option v-for="t in OUT_TYPES" :key="t.id" :value="t.id">{{ t.label }}</option>
              </optgroup>
              <optgroup label="Lainnya">
                <option value="adjustment">Penyesuaian opname (+/−)</option>
              </optgroup>
            </select>
            <p v-if="form.errors.type" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.type }}</p>
          </div>

          <div v-if="form.type !== 'adjustment'" class="grid grid-cols-2 gap-4">
            <div>
              <label class="field-label" for="m-qty">Jumlah ({{ target.unit }})</label>
              <input id="m-qty" v-model="form.qty" inputmode="numeric" class="field" />
              <p v-if="form.errors.qty" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.qty }}</p>
            </div>
            <div v-if="form.type === 'purchase' || form.type === 'supplier_return'">
              <label class="field-label" for="m-supplier">Supplier</label>
              <select id="m-supplier" v-model="form.supplier_id" class="field">
                <option value="">— tanpa supplier —</option>
                <option v-for="s in props.suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <p v-if="form.errors.supplier_id" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.supplier_id }}</p>
            </div>
          </div>

          <div v-if="form.type === 'adjustment'">
            <label class="field-label" for="m-delta">Selisih hasil hitung fisik (boleh minus)</label>
            <input id="m-delta" v-model="form.delta" inputmode="numeric" class="field" placeholder="mis. -3 atau 5" />
            <p v-if="form.errors.delta" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.delta }}</p>
          </div>

          <div>
            <label class="field-label" for="m-note">
              Catatan
              <span v-if="['damage', 'loss', 'adjustment'].includes(form.type)" class="text-danger">*</span>
            </label>
            <input id="m-note" v-model="form.note" class="field" placeholder="mis. No. PO, kondisi barang, alasan…" />
            <p v-if="form.errors.note" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.note }}</p>
          </div>

          <div class="flex items-center gap-3 border border-dashed border-line bg-ivory/60 px-4 py-3">
            <Scale class="h-4 w-4 text-gold" :stroke-width="1.5" />
            <p class="text-[0.8rem] text-forest">
              Stok menjadi
              <strong class="tabular-nums" :class="previewStock < 0 ? 'text-danger' : ''">{{ fmt(previewStock) }} {{ target.unit }}</strong>
              <span v-if="previewStock <= target.lowStock" class="ml-2 text-[0.72rem] text-[#7A5F26]">masuk zona menipis</span>
            </p>
          </div>
        </div>

        <div class="mt-6 flex gap-3">
          <AppButton type="submit" size="sm" :loading="form.processing">
            <template #icon><PackageMinus v-if="(DIRECTION[form.type] ?? 0) < 0" class="h-3.5 w-3.5" /><PackagePlus v-else class="h-3.5 w-3.5" /></template>
            Simpan gerakan
          </AppButton>
          <AppButton type="button" variant="quiet" size="sm" @click="showForm = false">Batal</AppButton>
        </div>
      </form>
    </div>

    <!-- Drawer riwayat mutasi -->
    <div v-if="showHistory && historyProduct" class="fixed inset-0 z-[90] flex justify-end bg-forest-deep/50 backdrop-blur-sm" @click.self="showHistory = false">
      <aside class="flex h-full w-full max-w-xl flex-col border-l border-line bg-surface">
        <header class="border-b border-line px-6 py-5">
          <p class="text-[0.68rem] uppercase tracking-[0.14em] text-gold">Kartu mutasi stok</p>
          <h2 class="mt-2 font-display text-2xl leading-none text-forest">{{ historyProduct.name }}</h2>
          <p class="mt-2 text-[0.78rem] text-muted">
            {{ historyProduct.sku }} · stok sekarang <strong class="text-forest">{{ fmt(historyProduct.stock) }} {{ historyProduct.unit }}</strong>
          </p>
        </header>

        <div class="flex-1 overflow-y-auto px-6 py-4">
          <p v-if="historyLoading" class="py-10 text-center text-[0.83rem] text-muted">Memuat riwayat…</p>
          <p v-else-if="!movements.length" class="py-10 text-center text-[0.83rem] text-muted">Belum ada gerakan stok.</p>
          <ol v-else class="space-y-3">
            <li v-for="m in movements" :key="m.id" class="border border-line bg-ivory/40 px-4 py-3">
              <div class="flex flex-wrap items-baseline justify-between gap-2">
                <p class="text-[0.85rem] font-medium text-forest">{{ m.label }}</p>
                <p class="text-[0.72rem] tabular-nums text-muted">{{ fmtDate(m.date) }}</p>
              </div>
              <div class="mt-2 grid grid-cols-3 gap-2 text-center text-[0.75rem]">
                <div class="bg-surface px-2 py-1.5">
                  <p class="text-muted">Masuk</p>
                  <p class="font-medium tabular-nums text-success">+{{ fmt(m.inbound) }}</p>
                </div>
                <div class="bg-surface px-2 py-1.5">
                  <p class="text-muted">Keluar</p>
                  <p class="font-medium tabular-nums text-danger">−{{ fmt(m.outbound) }}</p>
                </div>
                <div class="bg-surface px-2 py-1.5">
                  <p class="text-muted">Saldo</p>
                  <p class="font-medium tabular-nums text-forest">{{ fmt(m.balanceAfter) }}</p>
                </div>
              </div>
              <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[0.72rem] text-muted">
                <span>{{ m.documentNumber }}</span>
                <span v-if="m.orderNumber">Ref: {{ m.orderNumber }}</span>
                <span v-if="m.supplier">Supplier: {{ m.supplier }}</span>
                <span v-if="m.operator">Oleh: {{ m.operator }}</span>
              </p>
              <p v-if="m.note" class="mt-1 text-[0.72rem] italic text-muted">“{{ m.note }}”</p>
            </li>
          </ol>
        </div>

        <footer class="border-t border-line px-6 py-4">
          <AppButton variant="quiet" size="sm" @click="showHistory = false">Tutup</AppButton>
        </footer>
      </aside>
    </div>
  </div>
</template>