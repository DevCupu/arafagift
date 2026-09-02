<script setup>
import { computed, ref, watch } from 'vue'
import { ArrowDown, ArrowUp, ArrowUpDown, ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  columns: { type: Array, required: true }, // [{ key, label, align, width, sortKey, sortFn }]
  rows: { type: Array, default: () => [] },
  rowKey: { type: String, default: 'id' },
  defaultPageSize: { type: Number, default: 25 },
  pageSizeOptions: { type: Array, default: () => [25, 50, 100] },
  selectable: { type: Boolean, default: false },
  selected: { type: Array, default: () => [] },
})
const emit = defineEmits(['update:selected'])

// ── Sortir kolom ──
const sortKey = ref(null)
const sortDir = ref(1)

const sortableHeader = (c) => 'sortKey' in c || typeof c.sortFn === 'function' || c.sortable === true

const sortValue = (row, c) => (typeof c.sortFn === 'function' ? c.sortFn(row) : row[c.sortKey ?? c.key])

const ariaSort = (c) => {
  if (sortKey.value !== c.key) return 'none'
  return sortDir.value === 1 ? 'ascending' : 'descending'
}

const toggleSort = (c) => {
  if (!sortableHeader(c)) return
  if (sortKey.value === c.key) {
    if (sortDir.value === 1) sortDir.value = -1
    else { sortKey.value = null; sortDir.value = 1 }
  } else {
    sortKey.value = c.key
    sortDir.value = 1
  }
}

const sortedRows = computed(() => {
  if (!sortKey.value) return props.rows
  const col = props.columns.find((c) => c.key === sortKey.value)
  if (!col) return props.rows
  const dir = sortDir.value
  const copy = [...props.rows]
  copy.sort((a, b) => {
    const va = sortValue(a, col)
    const vb = sortValue(b, col)
    if (va == null && vb == null) return 0
    if (va == null) return 1
    if (vb == null) return -1
    const na = Number(va)
    const nb = Number(vb)
    if (va !== '' && vb !== '' && !Number.isNaN(na) && !Number.isNaN(nb)) return (na - nb) * dir
    return String(va).localeCompare(String(vb), 'id', { numeric: true }) * dir
  })
  return copy
})

// ── Paginasi ──
const size = ref(props.defaultPageSize)
const page = ref(1)

watch(() => props.rows, () => { page.value = 1 })

const total = computed(() => sortedRows.value.length)
const totalPages = computed(() => Math.max(1, Math.ceil(total.value / size.value)))
const clamped = computed(() => Math.min(page.value, totalPages.value))
const start = computed(() => (clamped.value - 1) * size.value + 1)
const end = computed(() => Math.min(start.value + size.value - 1, total.value))

const paginated = computed(() => {
  if (total.value <= size.value) return sortedRows.value
  return sortedRows.value.slice(start.value - 1, start.value - 1 + size.value)
})

const prevPage = () => { if (clamped.value > 1) page.value -= 1 }
const nextPage = () => { if (clamped.value < totalPages.value) page.value += 1 }
const changeSize = (e) => { size.value = Number(e.target.value); page.value = 1 }

// ── Seleksi baris (untuk bulk action) ──
const isSelected = (row) => props.selected.includes(row[props.rowKey])
const toggleRow = (row) => {
  const key = row[props.rowKey]
  emit('update:selected', isSelected(row) ? props.selected.filter((k) => k !== key) : [...props.selected, key])
}
const pageAllSelected = computed(() => paginated.value.length > 0 && paginated.value.every(isSelected))
const togglePage = () => {
  const pageKeys = paginated.value.map((r) => r[props.rowKey])
  emit('update:selected', pageAllSelected.value
    ? props.selected.filter((k) => !pageKeys.includes(k))
    : [...new Set([...props.selected, ...pageKeys])])
}
</script>

<template>
  <div class="border border-line bg-surface">
    <div class="overflow-x-auto">
      <table class="w-full min-w-[46rem] border-collapse text-left">
        <thead>
          <tr class="border-b border-line">
            <th v-if="selectable" class="w-10 px-5 py-3.5">
              <input
                type="checkbox" class="h-3.5 w-3.5 accent-olive" aria-label="Pilih semua baris di halaman ini"
                :checked="pageAllSelected" @change="togglePage"
              />
            </th>
            <th
              v-for="c in columns" :key="c.key"
              class="whitespace-nowrap px-5 py-3.5 text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-muted"
              :class="c.align === 'right' ? 'text-right' : ''"
              :style="c.width ? { width: c.width } : null"
              :aria-sort="c.key === sortKey ? ariaSort(c) : undefined"
            >
              <button
                v-if="sortableHeader(c)"
                type="button"
                class="group inline-flex items-center gap-1.5 uppercase tracking-[0.14em] transition hover:text-forest"
                :class="c.align === 'right' ? 'flex-row-reverse' : ''"
                @click="toggleSort(c)"
              >
                {{ c.label }}
                <component
                  :is="sortKey === c.key ? (sortDir === 1 ? ArrowUp : ArrowDown) : ArrowUpDown"
                  class="h-3 w-3 transition"
                  :class="sortKey === c.key ? 'text-gold' : 'text-muted/40 group-hover:text-forest'"
                />
              </button>
              <template v-else>{{ c.label }}</template>
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line">
          <tr v-for="row in paginated" :key="row[rowKey]" class="group transition hover:bg-ivory/70">
            <td v-if="selectable" class="px-5 py-4 align-middle">
              <input
                type="checkbox" class="h-3.5 w-3.5 accent-olive" :aria-label="`Pilih baris ${row[rowKey]}`"
                :checked="isSelected(row)" @change="toggleRow(row)"
              />
            </td>
            <td
              v-for="c in columns" :key="c.key"
              class="px-5 py-4 align-middle text-[0.85rem] text-forest"
              :class="c.align === 'right' ? 'text-right' : ''"
            >
              <slot :name="`cell-${c.key}`" :row="row">{{ row[c.key] }}</slot>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="!paginated.length" class="px-5 py-16 text-center">
        <p class="font-display text-xl text-forest">Tidak ada data pada filter ini</p>
        <p class="mt-2 text-[0.83rem] text-muted">Ubah kata kunci atau atur ulang filter untuk melihat baris lainnya.</p>
      </div>
    </div>

    <div v-if="total > size" class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-5 py-3.5">
      <p class="text-[0.75rem] text-muted">
        Menampilkan <strong class="tabular-nums text-forest">{{ start }}–{{ end }}</strong> dari <strong class="tabular-nums text-forest">{{ total }}</strong> baris
      </p>
      <div class="flex items-center gap-2">
        <label class="sr-only" for="page-size">Baris per halaman</label>
        <select
          id="page-size" :value="size" @change="changeSize"
          class="border border-line bg-surface px-2 py-1.5 text-[0.75rem] text-muted focus:border-olive focus:outline-none"
        >
          <option v-for="n in pageSizeOptions" :key="n" :value="n">{{ n }}/hal</option>
        </select>
        <button
          type="button" :disabled="clamped <= 1"
          class="grid h-7 w-7 place-items-center border border-line text-muted transition hover:text-forest disabled:opacity-35"
          aria-label="Halaman sebelumnya" @click="prevPage"
        >
          <ChevronLeft class="h-4 w-4" />
        </button>
        <span class="tabular-nums text-[0.75rem] text-muted">{{ clamped }} / {{ totalPages }}</span>
        <button
          type="button" :disabled="clamped >= totalPages"
          class="grid h-7 w-7 place-items-center border border-line text-muted transition hover:text-forest disabled:opacity-35"
          aria-label="Halaman berikutnya" @click="nextPage"
        >
          <ChevronRight class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>