<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Plus, Trash2 } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import StatusPill from '@/components/admin/StatusPill.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

defineProps({ promotions: { type: Array, required: true } })
const { push } = useToast()

const columns = [
  { key: 'code', label: 'Kode' },
  { key: 'type', label: 'Jenis' },
  { key: 'usage', label: 'Terpakai' },
  { key: 'period', label: 'Periode' },
  { key: 'status', label: 'Status' },
  { key: 'actions', label: '', align: 'right' },
]

const showForm = ref(false)
const form = useForm({ code: '', type: '', usage: '0 / 100', period: '', status: 'active' })

const save = () => {
  form.post('/admin/promo', {
    preserveScroll: true,
    onSuccess: () => {
      push('Kode promo dibuat', { tone: 'success' })
      form.reset()
      showForm.value = false
    },
  })
}

const destroy = (promo) => {
  if (!confirm(`Hapus kode promo "${promo.code}"?`)) return
  router.delete(`/admin/promo/${promo.id}`, {
    preserveScroll: true,
    onSuccess: () => push(`${promo.code} dihapus`, { tone: 'success' }),
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Promo</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Kode potongan yang bisa dipakai pembeli saat checkout.</p>
      </div>
      <AppButton size="sm" @click="showForm = !showForm">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Buat kode promo
      </AppButton>
    </header>

    <form v-if="showForm" class="mt-6 grid gap-4 border border-line bg-surface p-6 sm:grid-cols-2" @submit.prevent="save">
      <div>
        <label class="field-label" for="p-code">Kode</label>
        <input id="p-code" v-model="form.code" class="field uppercase" placeholder="PULANGHAJI" />
        <p v-if="form.errors.code" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.code }}</p>
      </div>
      <div>
        <label class="field-label" for="p-type">Jenis</label>
        <input id="p-type" v-model="form.type" class="field" placeholder="Potongan 10%" />
      </div>
      <div>
        <label class="field-label" for="p-period">Periode</label>
        <input id="p-period" v-model="form.period" class="field" placeholder="1–31 Ags 2026" />
      </div>
      <div>
        <label class="field-label" for="p-status">Status</label>
        <select id="p-status" v-model="form.status" class="field">
          <option value="active">Berjalan</option>
          <option value="ended">Berakhir</option>
        </select>
      </div>
      <div class="flex gap-3 sm:col-span-2">
        <AppButton type="submit" size="sm" :loading="form.processing">Simpan</AppButton>
        <AppButton type="button" variant="quiet" size="sm" @click="showForm = false">Batal</AppButton>
      </div>
    </form>

    <div class="mt-8">
      <DataTable :columns="columns" :rows="promotions" row-key="code">
        <template #cell-code="{ row }"><span class="font-medium tracking-wide">{{ row.code }}</span></template>
        <template #cell-usage="{ row }"><span class="text-muted">{{ row.usage }}</span></template>
        <template #cell-period="{ row }"><span class="text-muted">{{ row.period }}</span></template>
        <template #cell-status="{ row }">
          <StatusPill :label="row.status === 'active' ? 'Berjalan' : 'Berakhir'" :tone="row.status === 'active' ? 'success' : 'muted'" />
        </template>
        <template #cell-actions="{ row }">
          <button class="grid h-8 w-8 place-items-center text-muted transition hover:text-danger" aria-label="Hapus" @click="destroy(row)">
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </template>
      </DataTable>
    </div>
  </div>
</template>
