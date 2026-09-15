<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { ExternalLink, GripVertical, ImageOff, Pencil, Plus, Trash2 } from 'lucide-vue-next'
import BlockFields from '@/components/admin/BlockFields.vue'
import AppButton from '@/components/ui/AppButton.vue'
import AppModal from '@/components/ui/AppModal.vue'
import { BLOCKS, PRESETS, newBlock } from '@/components/landing/blocks.js'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  page: { type: Object, default: null },
  products: { type: Array, required: true },
})

const { push } = useToast()
const editing = Boolean(props.page)

const form = useForm({
  title: props.page?.title ?? '',
  slug: props.page?.slug ?? '',
  status: props.page?.status ?? 'draft',
  blocks: props.page?.blocks ?? [],
})

const slugify = () => {
  if (editing || form.slug) return
  form.slug = form.title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '')
}

// ── Ringkasan & thumbnail kartu blok, supaya daftar blok langsung "kebaca"
// tanpa harus membuka tiap satu ──
const productById = computed(() => new Map(props.products.map((p) => [p.id, p])))

const blockThumb = (block) => {
  const c = block.content
  if (block.type === 'hero') return c.image
  if (block.type === 'banner') return (c.items ?? []).find((it) => it.image)?.image
  if (block.type === 'nilai') return (c.items ?? []).find((it) => it.image)?.image
  if (block.type === 'testimoni') return (c.items ?? []).find((it) => it.avatar)?.avatar
  if (block.type === 'produk_unggulan') return productById.value.get(c.productIds?.[0])?.image
  return null
}

const blockSummary = (block) => {
  const c = block.content
  switch (block.type) {
    case 'hero': return c.headline || 'Belum diisi'
    case 'nilai': return `${(c.items ?? []).length} hooks`
    case 'produk_unggulan': return `${(c.productIds ?? []).length} produk dipilih`
    case 'testimoni': return `${(c.items ?? []).length} testimoni`
    case 'faq': return `${(c.items ?? []).length} pertanyaan`
    case 'cta': return c.headline || 'Belum diisi'
    default: return ''
  }
}

// ── Tambah / hapus blok ──
const adding = ref(false)
const addBlock = (type) => {
  form.blocks.push(newBlock(type))
  adding.value = false
  editIndex.value = form.blocks.length - 1
}

const removeBlock = (i) => {
  if (!confirm(`Hapus blok "${BLOCKS[form.blocks[i].type].label}"?`)) return
  form.blocks.splice(i, 1)
}

const applyPreset = (name) => {
  if (form.blocks.length && !confirm('Ganti semua blok dengan preset ini?')) return
  form.blocks = PRESETS[name].map(newBlock)
}

// ── Edit blok lewat popup, bukan accordion inline ──
const editIndex = ref(null)
const editingBlock = computed(() => (editIndex.value === null ? null : form.blocks[editIndex.value]))

// ── Drag & drop native, tanpa dependency ──
const dragFrom = ref(null)
const dragOver = ref(null)

const onDrop = (to) => {
  const from = dragFrom.value
  dragFrom.value = null
  dragOver.value = null
  if (from === null || from === to) return
  const [moved] = form.blocks.splice(from, 1)
  form.blocks.splice(to, 0, moved)
}

// ── Simpan ──
const save = () => {
  const opts = {
    preserveScroll: true,
    onSuccess: () => push(editing ? 'Landing disimpan' : 'Landing dibuat', { tone: 'success' }),
    onError: () => push('Ada isian yang belum benar. Cek blok bertanda merah.', { tone: 'danger' }),
  }
  if (editing) form.put(`/admin/landing/${props.page.slug}`, opts)
  else form.post('/admin/landing', opts)
}

// Error Laravel "blocks.0.content.headline" → tandai kartu blok ke-0.
const blockHasError = computed(() => {
  const flags = new Set()
  for (const key of Object.keys(form.errors)) {
    const match = key.match(/^blocks\.(\d+)\./)
    if (match) flags.add(Number(match[1]))
  }
  return flags
})
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">{{ editing ? page.title : 'Landing baru' }}</h1>
        <p class="mt-3 text-[0.85rem] text-muted">
          Susun halaman dari blok. Seret kartu untuk mengubah urutan, klik untuk mengisi kontennya.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <a
          v-if="editing" :href="`/${page.slug}`" target="_blank" rel="noopener"
          class="inline-flex items-center gap-1.5 text-[0.78rem] text-muted transition hover:text-forest"
        >Lihat halaman<ExternalLink class="h-3 w-3" /></a>
        <AppButton size="sm" :loading="form.processing" @click="save">Simpan</AppButton>
      </div>
    </header>

    <!-- Pengaturan halaman -->
    <div class="mt-6 grid gap-4 border border-line bg-surface p-6 sm:grid-cols-3">
      <div>
        <label class="field-label" for="l-title">Judul halaman</label>
        <input id="l-title" v-model="form.title" class="field" placeholder="ArafahGift untuk Agen Travel" @blur="slugify" />
        <p v-if="form.errors.title" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.title }}</p>
      </div>
      <div>
        <label class="field-label" for="l-slug">Alamat</label>
        <div class="flex items-center gap-1">
          <span class="text-[0.85rem] text-muted">/</span>
          <input id="l-slug" v-model="form.slug" class="field" placeholder="arafagifttravel" />
        </div>
        <p v-if="form.errors.slug" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.slug }}</p>
      </div>
      <div>
        <label class="field-label" for="l-status">Status</label>
        <select id="l-status" v-model="form.status" class="field">
          <option value="draft">Draft — hanya admin yang bisa lihat</option>
          <option value="publish">Tayang</option>
        </select>
      </div>
    </div>

    <!-- Preset -->
    <div class="mt-6 flex flex-wrap items-center gap-3">
      <span class="text-[0.78rem] text-muted">Mulai dari preset:</span>
      <button
        v-for="(types, name) in PRESETS" :key="name" type="button"
        class="border border-line bg-surface px-3 py-1.5 text-[0.78rem] text-forest transition hover:bg-sand/40"
        @click="applyPreset(name)"
      >{{ name }} <span class="text-muted">({{ types.length }} blok)</span></button>
    </div>

    <!-- Daftar blok: kartu dengan thumbnail, klik untuk buka popup edit -->
    <ul class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <li
        v-for="(block, i) in form.blocks" :key="i"
        draggable="true"
        class="group relative cursor-pointer overflow-hidden border bg-surface shadow-soft transition hover:shadow-lift"
        :class="[
          blockHasError.has(i) ? 'border-danger' : 'border-line',
          dragOver === i && dragFrom !== i ? 'border-gold ring-1 ring-gold' : '',
          dragFrom === i ? 'opacity-40' : '',
          block.enabled ? '' : 'opacity-50',
        ]"
        @dragstart="dragFrom = i"
        @dragover.prevent="dragOver = i"
        @drop.prevent="onDrop(i)"
        @dragend="dragFrom = null; dragOver = null"
        @click="editIndex = i"
      >
        <!-- Thumbnail -->
        <div class="relative aspect-[16/9] overflow-hidden bg-sand">
          <img v-if="blockThumb(block)" :src="blockThumb(block)" alt="" class="h-full w-full object-cover" />
          <div v-else class="grid h-full place-items-center text-muted/50"><ImageOff class="h-6 w-6" /></div>

          <span
            class="absolute left-2 top-2 border border-line/60 bg-surface/90 px-2 py-0.5 text-[0.68rem] font-medium uppercase tracking-wide text-forest backdrop-blur"
          >{{ BLOCKS[block.type].label }}</span>

          <span
            class="absolute right-2 top-2 h-2 w-2 rounded-full"
            :class="block.enabled ? 'bg-forest-soft' : 'bg-muted'"
            :title="block.enabled ? 'Aktif' : 'Nonaktif'"
          />

          <div class="absolute inset-0 flex items-center justify-center bg-forest-deep/0 opacity-0 transition group-hover:bg-forest-deep/30 group-hover:opacity-100">
            <span class="flex items-center gap-1.5 bg-surface px-3 py-1.5 text-[0.78rem] font-medium text-forest">
              <Pencil class="h-3.5 w-3.5" /> Edit blok
            </span>
          </div>
        </div>

        <!-- Ringkasan -->
        <div class="flex items-center gap-3 p-3">
          <GripVertical class="h-4 w-4 flex-none cursor-grab text-muted" @click.stop />
          <p class="min-w-0 flex-1 truncate text-[0.8rem] text-forest">{{ blockSummary(block) }}</p>
          <button
            type="button" class="grid h-7 w-7 flex-none place-items-center text-muted transition hover:text-danger"
            :aria-label="`Hapus blok ${BLOCKS[block.type].label}`" @click.stop="removeBlock(i)"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </div>
      </li>

      <!-- Tambah blok -->
      <li class="relative">
        <button
          type="button"
          class="flex h-full min-h-[11rem] w-full flex-col items-center justify-center gap-2 border border-dashed border-line text-[0.82rem] text-forest transition hover:bg-sand/40"
          @click="adding = !adding"
        >
          <Plus class="h-5 w-5" /> Tambah blok
        </button>
        <div v-if="adding" class="absolute z-10 mt-1 w-full min-w-[16rem] border border-line bg-surface shadow-lift sm:w-72">
          <button
            v-for="(def, type) in BLOCKS" :key="type" type="button"
            class="flex w-full flex-col items-start border-b border-line px-4 py-2.5 text-left text-[0.82rem] last:border-b-0 hover:bg-sand/40"
            @click="addBlock(type)"
          >
            <span class="font-medium text-forest">{{ def.label }}</span>
            <span class="text-[0.72rem] text-muted">{{ def.hint }}</span>
          </button>
        </div>
      </li>
    </ul>

    <p v-if="form.errors.blocks" class="mt-3 text-[0.75rem] text-danger">{{ form.errors.blocks }}</p>

    <div class="mt-8">
      <AppButton :loading="form.processing" @click="save">Simpan</AppButton>
    </div>

    <!-- Popup edit blok -->
    <AppModal :open="editIndex !== null" label="Edit blok" @close="editIndex = null">
      <div v-if="editingBlock" class="p-6 sm:p-8">
        <div class="mb-6 flex items-start justify-between gap-4 pr-10">
          <div>
            <p class="eyebrow">{{ BLOCKS[editingBlock.type].label }}</p>
            <h2 class="mt-2 text-[1.4rem]">{{ BLOCKS[editingBlock.type].hint }}</h2>
          </div>
          <label class="flex flex-none cursor-pointer items-center gap-2 text-[0.78rem] text-muted">
            <input v-model="editingBlock.enabled" type="checkbox" />
            Aktif
          </label>
        </div>

        <BlockFields
          :fields="BLOCKS[editingBlock.type].fields" :model="editingBlock.content"
          :products="products" :errors="form.errors" :error-prefix="`blocks.${editIndex}.content`"
        />

        <div class="mt-8 flex justify-end border-t border-line pt-5">
          <AppButton size="sm" @click="editIndex = null">Selesai</AppButton>
        </div>
      </div>
    </AppModal>
  </div>
</template>
