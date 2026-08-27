<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import DataTable from '@/components/admin/DataTable.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ suppliers: { type: Array, required: true } })
const { push } = useToast()

const showForm = ref(false)
const editingId = ref(null)
const form = useForm({ name: '', phone: '', email: '', address: '', note: '' })

const startCreate = () => {
  editingId.value = null
  form.reset()
  showForm.value = true
}

const startEdit = (s) => {
  editingId.value = s.id
  form.name = s.name
  form.phone = s.phone ?? ''
  form.email = s.email ?? ''
  form.address = s.address ?? ''
  form.note = s.note ?? ''
  showForm.value = true
}

const save = () => {
  const onSuccess = () => {
    push(editingId.value ? 'Data supplier diperbarui' : 'Supplier baru ditambahkan', { tone: 'success' })
    showForm.value = false
  }
  if (editingId.value) {
    form.put(`/admin/supplier/${editingId.value}`, { preserveScroll: true, onSuccess })
  } else {
    form.post('/admin/supplier', { preserveScroll: true, onSuccess })
  }
}

const destroy = (s) => {
  if (!confirm(`Hapus supplier "${s.name}"?`)) return
  router.delete(`/admin/supplier/${s.id}`, {
    preserveScroll: true,
    onSuccess: () => push(`${s.name} dihapus`, { tone: 'success' }),
    onError: (errors) => push(errors.supplier ?? 'Gagal menghapus supplier', { tone: 'danger' }),
  })
}

const columns = [
  { key: 'name', label: 'Supplier' },
  { key: 'contact', label: 'Kontak' },
  { key: 'address', label: 'Alamat' },
  { key: 'productCount', label: 'Produk', align: 'right' },
  { key: 'actions', label: '' },
]
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Supplier</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Daftar pemasok barang. Dipakai saat mencatat pembelian di inventori.</p>
      </div>
      <AppButton size="sm" @click="startCreate">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Tambah supplier
      </AppButton>
    </header>

    <form v-if="showForm" class="mt-6 grid gap-4 border border-line bg-surface p-6 sm:grid-cols-2" @submit.prevent="save">
      <div>
        <label class="field-label" for="s-name">Nama</label>
        <input id="s-name" v-model="form.name" class="field" />
        <p v-if="form.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.name }}</p>
      </div>
      <div>
        <label class="field-label" for="s-phone">Telepon</label>
        <input id="s-phone" v-model="form.phone" class="field" inputmode="tel" />
        <p v-if="form.errors.phone" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.phone }}</p>
      </div>
      <div>
        <label class="field-label" for="s-email">Email</label>
        <input id="s-email" v-model="form.email" class="field" type="email" />
        <p v-if="form.errors.email" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.email }}</p>
      </div>
      <div>
        <label class="field-label" for="s-note">Catatan</label>
        <input id="s-note" v-model="form.note" class="field" placeholder="mis. spesialis kurma ajwa" />
      </div>
      <div class="sm:col-span-2">
        <label class="field-label" for="s-address">Alamat</label>
        <input id="s-address" v-model="form.address" class="field" />
        <p v-if="form.errors.address" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.address }}</p>
      </div>
      <div class="flex gap-3 sm:col-span-2">
        <AppButton type="submit" size="sm" :loading="form.processing">Simpan</AppButton>
        <AppButton type="button" variant="quiet" size="sm" @click="showForm = false">Batal</AppButton>
      </div>
    </form>

    <div class="mt-8">
      <DataTable :columns="columns" :rows="props.suppliers">
        <template #cell-name="{ row }"><span class="font-medium">{{ row.name }}</span></template>
        <template #cell-contact="{ row }">
          <span class="block text-muted">{{ row.phone ?? '–' }}</span>
          <span class="block text-[0.72rem] text-muted">{{ row.email ?? '' }}</span>
        </template>
        <template #cell-address="{ row }"><span class="text-muted">{{ row.address ?? '–' }}</span></template>
        <template #cell-productCount="{ row }">
          <span class="tabular-nums text-muted">{{ row.productCount }}</span>
        </template>
        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-3">
            <button class="text-[0.78rem] text-forest underline underline-offset-4" @click="startEdit(row)">Ubah</button>
            <button class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="destroy(row)">Hapus</button>
          </div>
        </template>
      </DataTable>
    </div>
  </div>
</template>
