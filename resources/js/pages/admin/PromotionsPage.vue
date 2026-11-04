<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Pencil, Plus, Trash2, X } from 'lucide-vue-next'
import BulkActionBar from '@/components/admin/BulkActionBar.vue'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { formatIDR } from '@/composables/useFormat'
import { useToast } from '@/composables/useToast'

const props = defineProps({ promotions: { type: Array, required: true } })
const { push } = useToast()

const columns = [
  { key: 'code', label: 'Kode' },
  { key: 'discount', label: 'Diskon' },
  { key: 'minimum', label: 'Min. belanja' },
  { key: 'usage', label: 'Terpakai' },
  { key: 'period', label: 'Periode' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' },
]

const blank = {
  code: '',
  type: 'Diskon persen',
  usage: '0 / 100',
  period: '',
  status: 'active',
  discount_type: 'percent',
  discount_value: 10,
  min_purchase: 0,
  max_discount: null,
  starts_at: '',
  ends_at: '',
  usage_limit: null,
  applies_to: 'all',
  applies_value: '',
  is_active: true,
}

const showForm = ref(false)
const editingId = ref(null)
const form = useForm({ ...blank })

const isEditing = computed(() => editingId.value !== null)

const toLocalInput = (value) => {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value).slice(0, 16)
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const openCreate = () => {
  editingId.value = null
  form.defaults({ ...blank })
  form.reset()
  showForm.value = true
}

const openEdit = (promo) => {
  editingId.value = promo.id
  form.defaults({
    code: promo.code ?? '',
    type: promo.type ?? 'Diskon persen',
    usage: promo.usage ?? '0 / 100',
    period: promo.period ?? '',
    status: promo.status ?? 'active',
    discount_type: promo.discount_type ?? 'percent',
    discount_value: promo.discount_value ?? 0,
    min_purchase: promo.min_purchase ?? 0,
    max_discount: promo.max_discount ?? null,
    starts_at: toLocalInput(promo.starts_at),
    ends_at: toLocalInput(promo.ends_at),
    usage_limit: promo.usage_limit ?? null,
    applies_to: promo.applies_to ?? 'all',
    applies_value: promo.applies_value ?? '',
    is_active: promo.is_active ?? true,
  })
  form.reset()
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  editingId.value = null
}

const save = () => {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      push(isEditing.value ? 'Kode promo diperbarui' : 'Kode promo dibuat', { tone: 'success' })
      closeForm()
      form.reset()
    },
  }

  if (isEditing.value) {
    form.put(`/admin/promo/${editingId.value}`, options)
  } else {
    form.post('/admin/promo', options)
  }
}

const destroy = (promo) => {
  if (!confirm(`Hapus kode promo "${promo.code}"?`)) return
  router.delete(`/admin/promo/${promo.id}`, {
    preserveScroll: true,
    onSuccess: () => push(`${promo.code} dihapus`, { tone: 'success' }),
  })
}

const selectedCodes = ref([])
const bulkDeleting = ref(false)
const bulkDelete = () => {
  const ids = props.promotions.filter((p) => selectedCodes.value.includes(p.code)).map((p) => p.id)
  bulkDeleting.value = true
  router.delete('/admin/promo/bulk', {
    data: { ids },
    preserveScroll: true,
    onSuccess: () => { push(`${ids.length} kode promo dihapus`, { tone: 'success' }); selectedCodes.value = [] },
    onFinish: () => { bulkDeleting.value = false },
  })
}

const discountText = (promo) => promo.discount_type === 'fixed'
  ? formatIDR(promo.discount_value)
  : `${promo.discount_value}%`
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Promo & Voucher</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Kode potongan yang bisa dipakai pembeli saat checkout.</p>
      </div>
      <AppButton size="sm" @click="showForm ? closeForm() : openCreate()">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Buat kode promo
      </AppButton>
    </header>

    <form v-if="showForm" class="mt-6 space-y-5 border border-line bg-surface p-6" @submit.prevent="save">
      <div class="flex items-center justify-between gap-4">
        <h2 class="font-display text-xl text-forest">{{ isEditing ? 'Edit kode promo' : 'Kode promo baru' }}</h2>
        <button type="button" class="text-muted transition hover:text-forest" aria-label="Tutup form" @click="closeForm">
          <X class="h-4 w-4" />
        </button>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="field-label" for="p-code">Kode</label>
          <input id="p-code" v-model="form.code" class="field uppercase" placeholder="PULANGHAJI" />
          <p v-if="form.errors.code" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.code }}</p>
        </div>
        <div>
          <label class="field-label" for="p-type">Jenis / label</label>
          <input id="p-type" v-model="form.type" class="field" placeholder="Potongan 10%" />
        </div>

        <div>
          <label class="field-label" for="p-discount-type">Tipe diskon</label>
          <select id="p-discount-type" v-model="form.discount_type" class="field">
            <option value="percent">Persen (%)</option>
            <option value="fixed">Nominal (Rp)</option>
          </select>
          <p v-if="form.errors.discount_type" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.discount_type }}</p>
        </div>
        <div>
          <label class="field-label" for="p-discount-value">
            Nilai diskon {{ form.discount_type === 'percent' ? '(%)' : '(Rp)' }}
          </label>
          <input id="p-discount-value" v-model.number="form.discount_value" type="number" min="0" class="field" placeholder="10" />
          <p v-if="form.errors.discount_value" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.discount_value }}</p>
        </div>

        <div>
          <label class="field-label" for="p-min">Minimum belanja (Rp)</label>
          <input id="p-min" v-model.number="form.min_purchase" type="number" min="0" class="field" placeholder="0" />
          <p class="mt-1 text-[0.7rem] text-muted">0 = tanpa minimum.</p>
        </div>
        <div>
          <label class="field-label" for="p-max">Maksimal potongan (Rp)</label>
          <input id="p-max" v-model.number="form.max_discount" type="number" min="0" class="field" placeholder="Kosong = tanpa batas" />
          <p class="mt-1 text-[0.7rem] text-muted">Berguna untuk diskon persen.</p>
        </div>

        <div>
          <label class="field-label" for="p-starts">Mulai berlaku</label>
          <input id="p-starts" v-model="form.starts_at" type="datetime-local" class="field" />
        </div>
        <div>
          <label class="field-label" for="p-ends">Berakhir</label>
          <input id="p-ends" v-model="form.ends_at" type="datetime-local" class="field" />
          <p v-if="form.errors.ends_at" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.ends_at }}</p>
        </div>

        <div>
          <label class="field-label" for="p-limit">Batas penggunaan</label>
          <input id="p-limit" v-model.number="form.usage_limit" type="number" min="0" class="field" placeholder="Kosong = tanpa batas" />
        </div>
        <div>
          <label class="field-label" for="p-period">Label periode</label>
          <input id="p-period" v-model="form.period" class="field" placeholder="1–31 Ags 2026" />
        </div>

        <div>
          <label class="field-label" for="p-applies">Berlaku untuk</label>
          <select id="p-applies" v-model="form.applies_to" class="field">
            <option value="all">Semua produk</option>
            <option value="category">Kategori tertentu</option>
            <option value="product">Produk tertentu</option>
          </select>
        </div>
        <div v-if="form.applies_to !== 'all'">
          <label class="field-label" for="p-applies-value">
            {{ form.applies_to === 'category' ? 'ID kategori' : 'ID produk' }}
          </label>
          <input id="p-applies-value" v-model="form.applies_value" class="field" placeholder="Pisahkan dengan koma, mis. 1,2,3" />
          <p class="mt-1 text-[0.7rem] text-muted">Lihat ID di halaman kategori/produk terkait.</p>
        </div>

        <div>
          <label class="field-label" for="p-status">Status</label>
          <select id="p-status" v-model="form.status" class="field">
            <option value="active">Berjalan</option>
            <option value="ended">Berakhir</option>
          </select>
        </div>
        <label class="flex items-center gap-2 self-end pb-3 text-[0.85rem] text-forest">
          <input v-model="form.is_active" type="checkbox" class="h-4 w-4 accent-[rgb(var(--c-forest))]" />
          Aktif bisa dipakai checkout
        </label>
      </div>

      <div class="flex gap-3">
        <AppButton type="submit" size="sm" :loading="form.processing">Simpan</AppButton>
        <AppButton type="button" variant="quiet" size="sm" @click="closeForm">Batal</AppButton>
      </div>
    </form>

    <div class="mt-8">
      <BulkActionBar
        :count="selectedCodes.length" label="kode promo" class="mb-3" :loading="bulkDeleting"
        @clear="selectedCodes = []" @delete="bulkDelete"
      />
      <DataTable
        :columns="columns" :rows="promotions" row-key="code" selectable v-model:selected="selectedCodes"
        :base-empty="promotions.length === 0" empty-title="Belum ada kode promo"
        empty-body="Buat kode potongan pertama untuk dipakai pembeli saat checkout."
      >
        <template #cell-code="{ row }"><span class="font-medium tracking-wide">{{ row.code }}</span></template>
        <template #cell-discount="{ row }">
          <span class="font-semibold text-forest">{{ discountText(row) }}</span>
          <span v-if="row.max_discount" class="block text-[0.68rem] text-muted">maks {{ formatIDR(row.max_discount) }}</span>
        </template>
        <template #cell-minimum="{ row }">
          <span class="text-muted">{{ row.min_purchase > 0 ? formatIDR(row.min_purchase) : '—' }}</span>
        </template>
        <template #cell-usage="{ row }">
          <span class="text-muted">{{ row.used_count ?? 0 }}{{ row.usage_limit ? ` / ${row.usage_limit}` : '' }}</span>
        </template>
        <template #cell-period="{ row }">
          <span class="text-muted">{{ row.period || '—' }}</span>
          <span v-if="row.starts_at || row.ends_at" class="mt-0.5 block text-[0.68rem] text-muted">
            {{ row.starts_at ? toLocalInput(row.starts_at).replace('T', ' ') : '…' }}
            –
            {{ row.ends_at ? toLocalInput(row.ends_at).replace('T', ' ') : '…' }}
          </span>
        </template>
        <template #cell-status="{ row }">
          <StatusPill
            :label="row.is_active && row.status === 'active' ? 'Aktif' : 'Nonaktif'"
            :tone="row.is_active && row.status === 'active' ? 'success' : 'muted'"
          />
        </template>
        <template #cell-actions="{ row }">
          <div class="flex items-center justify-end gap-1">
            <button type="button" class="grid h-8 w-8 place-items-center text-muted transition hover:text-forest" aria-label="Edit" @click="openEdit(row)">
              <Pencil class="h-3.5 w-3.5" />
            </button>
            <button type="button" class="grid h-8 w-8 place-items-center text-muted transition hover:text-danger" aria-label="Hapus" @click="destroy(row)">
              <Trash2 class="h-3.5 w-3.5" />
            </button>
          </div>
        </template>
        <template #empty-action>
          <AppButton size="sm" @click="openCreate">
            <template #icon><Plus class="h-3.5 w-3.5" /></template>
            Buat kode promo
          </AppButton>
        </template>
      </DataTable>
    </div>
  </div>
</template>
