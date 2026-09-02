<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { ImagePlus, Plus } from 'lucide-vue-next'
import BulkActionBar from '@/components/admin/BulkActionBar.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({ categories: { type: Array, required: true } })
const { push } = useToast()

const showForm = ref(false)
const editingSlug = ref(null)
const imagePreview = ref(null)
const form = useForm({ name: '', slug: '', art: 'giftset', tagline: '', image: null })

const slugify = () => {
  if (!editingSlug.value) form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

const startCreate = () => {
  editingSlug.value = null
  form.reset()
  imagePreview.value = null
  showForm.value = true
}

const startEdit = (c) => {
  editingSlug.value = c.slug
  form.name = c.name
  form.slug = c.slug
  form.art = c.art
  form.tagline = c.tagline ?? ''
  form.image = null
  imagePreview.value = c.image ?? null
  showForm.value = true
}

const onImageChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  form.image = file
  imagePreview.value = URL.createObjectURL(file)
}

const save = () => {
  const editing = editingSlug.value
  form
    .transform((data) => (editing ? { ...data, _method: 'put' } : data))
    .post(editing ? `/admin/kategori/${editing}` : '/admin/kategori', {
      preserveScroll: true,
      onSuccess: () => {
        push(editing ? 'Kategori diperbarui' : 'Kategori baru dibuat', { tone: 'success' })
        showForm.value = false
      },
    })
}

const destroy = (c) => {
  if (!confirm(`Hapus kategori "${c.name}"?`)) return
  router.delete(`/admin/kategori/${c.slug}`, {
    preserveScroll: true,
    onSuccess: () => push(`${c.name} dihapus`, { tone: 'success' }),
    onError: (errors) => push(errors.category ?? 'Gagal menghapus kategori', { tone: 'danger' }),
  })
}

const selected = ref([])
const bulkDeleting = ref(false)
const bulkDelete = () => {
  bulkDeleting.value = true
  router.delete('/admin/kategori/bulk', {
    data: { ids: selected.value },
    preserveScroll: true,
    onSuccess: () => { push(`${selected.value.length} kategori dihapus`, { tone: 'success' }); selected.value = [] },
    onError: (errors) => push(errors.category ?? 'Gagal menghapus sebagian kategori', { tone: 'danger' }),
    onFinish: () => { bulkDeleting.value = false },
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Kategori</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Urutan kategori di sini menentukan urutan di homepage.</p>
      </div>
      <AppButton size="sm" @click="startCreate">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Tambah kategori
      </AppButton>
    </header>

    <form v-if="showForm" class="mt-6 grid gap-4 border border-line bg-surface p-6 sm:grid-cols-2" @submit.prevent="save">
      <div>
        <label class="field-label" for="c-name">Nama</label>
        <input id="c-name" v-model="form.name" class="field" @blur="slugify" />
        <p v-if="form.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.name }}</p>
      </div>
      <div>
        <label class="field-label" for="c-slug">Slug</label>
        <input id="c-slug" v-model="form.slug" class="field" />
        <p v-if="form.errors.slug" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.slug }}</p>
      </div>
      <div>
        <label class="field-label" for="c-art">Motif ikon</label>
        <input id="c-art" v-model="form.art" class="field" placeholder="giftset, kurma, sajadah, ..." />
        <p v-if="form.errors.art" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.art }}</p>
      </div>
      <div>
        <label class="field-label" for="c-tagline">Tagline</label>
        <input id="c-tagline" v-model="form.tagline" class="field" />
      </div>
      <div class="sm:col-span-2">
        <span class="field-label">Foto kategori</span>
        <div class="mt-2 flex items-center gap-4">
          <div class="arch h-24 w-[72px] flex-none overflow-hidden border border-line bg-ivory">
            <img v-if="imagePreview" :src="imagePreview" alt="Foto kategori" class="h-full w-full object-cover" />
            <ProductArt v-else :art="form.art" :tone="0" />
          </div>
          <label class="flex cursor-pointer items-center gap-2 border border-dashed border-line bg-ivory/60 px-4 py-3 text-muted transition hover:border-olive/60 hover:text-forest">
            <ImagePlus class="h-4 w-4 text-gold" :stroke-width="1.4" />
            <span class="text-[0.75rem]">Unggah foto</span>
            <input type="file" accept="image/*" class="sr-only" @change="onImageChange" />
          </label>
        </div>
        <p class="mt-2 text-[0.72rem] text-muted">JPG/PNG/WEBP, maks 4 MB. Dipakai sebagai cover di kartu kategori.</p>
        <p v-if="form.errors.image" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.image }}</p>
      </div>
      <div class="flex gap-3 sm:col-span-2">
        <AppButton type="submit" size="sm" :loading="form.processing">Simpan</AppButton>
        <AppButton type="button" variant="quiet" size="sm" @click="showForm = false">Batal</AppButton>
      </div>
    </form>

    <BulkActionBar
      :count="selected.length" label="kategori" class="mt-8" :loading="bulkDeleting"
      @clear="selected = []" @delete="bulkDelete"
    />
    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="(c, i) in categories" :key="c.slug" class="flex gap-4 border border-line bg-surface p-4">
        <label class="flex-none pt-1">
          <span class="sr-only">Pilih {{ c.name }}</span>
          <input type="checkbox" class="h-3.5 w-3.5 accent-olive" :value="c.id" v-model="selected" />
        </label>
        <span class="arch h-24 w-[72px] flex-none overflow-hidden border border-line bg-ivory">
          <img v-if="c.image" :src="c.image" :alt="c.name" class="h-full w-full object-cover" />
          <ProductArt v-else :art="c.art" :tone="i" />
        </span>
        <div class="flex flex-1 flex-col">
          <h2 class="font-display text-xl leading-none text-forest">{{ c.name }}</h2>
          <p class="mt-2 text-[0.78rem] text-muted">/koleksi/{{ c.slug }}</p>
          <p class="mt-1 text-[0.78rem] text-muted">{{ c.count }} produk aktif</p>
          <div class="mt-auto flex gap-3 pt-3">
            <button type="button" class="text-[0.78rem] text-forest underline underline-offset-4" @click="startEdit(c)">Ubah</button>
            <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="destroy(c)">Hapus</button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
