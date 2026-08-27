<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
import AccountShell from '@/components/storefront/AccountShell.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppBadge from '@/components/ui/AppBadge.vue'
import { useToast } from '@/composables/useToast'

defineProps({ addresses: { type: Array, required: true } })
const { push } = useToast()

const showForm = ref(false)
const editingId = ref(null)
const form = useForm({ label: '', recipient_name: '', phone: '', address_text: '' })

const startCreate = () => {
  editingId.value = null
  form.reset()
  showForm.value = true
}

const startEdit = (a) => {
  editingId.value = a.id
  form.label = a.label
  form.recipient_name = a.recipient_name
  form.phone = a.phone
  form.address_text = a.address_text
  showForm.value = true
}

const save = () => {
  const onSuccess = () => {
    push(editingId.value ? 'Alamat diperbarui' : 'Alamat baru disimpan', { tone: 'success' })
    showForm.value = false
  }
  if (editingId.value) {
    form.put(`/akun/alamat/${editingId.value}`, { preserveScroll: true, onSuccess })
  } else {
    form.post('/akun/alamat', { preserveScroll: true, onSuccess })
  }
}

const setPrimary = (a) => {
  router.patch(`/akun/alamat/${a.id}/utama`, {}, {
    preserveScroll: true,
    onSuccess: () => push('Alamat utama diperbarui', { tone: 'success' }),
  })
}

const destroy = (a) => {
  if (!confirm(`Hapus alamat "${a.label}"?`)) return
  router.delete(`/akun/alamat/${a.id}`, {
    preserveScroll: true,
    onSuccess: () => push('Alamat dihapus', { tone: 'success' }),
  })
}
</script>

<template>
  <AccountShell title="Alamat" sub="Simpan alamat penerima supaya checkout berikutnya lebih cepat.">
    <form v-if="showForm" class="mb-6 grid gap-4 border border-line bg-surface p-6 sm:grid-cols-2" @submit.prevent="save">
      <div>
        <label class="field-label" for="a-label">Label</label>
        <input id="a-label" v-model="form.label" class="field" placeholder="Rumah, Kantor, ..." />
        <p v-if="form.errors.label" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.label }}</p>
      </div>
      <div>
        <label class="field-label" for="a-name">Nama penerima</label>
        <input id="a-name" v-model="form.recipient_name" class="field" />
        <p v-if="form.errors.recipient_name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.recipient_name }}</p>
      </div>
      <div>
        <label class="field-label" for="a-phone">Nomor telepon</label>
        <input id="a-phone" v-model="form.phone" class="field" />
        <p v-if="form.errors.phone" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.phone }}</p>
      </div>
      <div class="sm:col-span-2">
        <label class="field-label" for="a-text">Alamat lengkap</label>
        <textarea id="a-text" v-model="form.address_text" rows="3" class="field" />
        <p v-if="form.errors.address_text" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.address_text }}</p>
      </div>
      <div class="flex gap-3 sm:col-span-2">
        <AppButton type="submit" size="sm" :loading="form.processing">Simpan</AppButton>
        <AppButton type="button" variant="quiet" size="sm" @click="showForm = false">Batal</AppButton>
      </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
      <article v-for="a in addresses" :key="a.id" class="border border-line bg-surface p-6">
        <div class="flex items-center justify-between">
          <p class="text-[0.72rem] uppercase tracking-[0.14em] text-olive">{{ a.label }}</p>
          <AppBadge v-if="a.is_primary">Utama</AppBadge>
        </div>
        <p class="mt-4 font-display text-xl text-forest">{{ a.recipient_name }}</p>
        <p class="mt-2 text-[0.85rem] leading-relaxed text-muted">{{ a.address_text }}</p>
        <p class="mt-2 text-[0.85rem] text-muted">{{ a.phone }}</p>
        <div class="mt-5 flex gap-3">
          <button class="text-[0.8rem] text-forest underline underline-offset-4" @click="startEdit(a)">Ubah</button>
          <button v-if="!a.is_primary" class="text-[0.8rem] text-muted underline underline-offset-4 hover:text-forest" @click="setPrimary(a)">
            Jadikan utama
          </button>
          <button class="text-[0.8rem] text-muted underline underline-offset-4 hover:text-danger" @click="destroy(a)">Hapus</button>
        </div>
      </article>

      <button class="flex min-h-[12rem] flex-col items-center justify-center gap-3 border border-dashed border-line bg-surface/60 text-muted transition hover:border-olive/60 hover:text-forest" @click="startCreate">
        <Plus class="h-5 w-5 text-gold" :stroke-width="1.4" />
        <span class="text-[0.85rem]">Tambah alamat baru</span>
      </button>
    </div>
  </AccountShell>
</template>
