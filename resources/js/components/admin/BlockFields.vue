<script setup>
import { ref } from 'vue'
import { Plus, Trash2 } from 'lucide-vue-next'
import { ICONS, getPath, setPath } from '@/components/landing/blocks.js'

const props = defineProps({
  fields: { type: Array, required: true },
  model: { type: Object, required: true },
  products: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  // Prefix path error dari Laravel, mis. "blocks.0.content"
  errorPrefix: { type: String, default: '' },
})

const get = (key) => getPath(props.model, key)
const set = (key, value) => setPath(props.model, key, value)

const errorFor = (key) => props.errors[`${props.errorPrefix}.${key}`]

const uploading = ref({})

const onImage = async (key, event) => {
  const file = event.target.files?.[0]
  if (!file) return

  uploading.value[key] = true
  try {
    const body = new FormData()
    body.append('image', file)
    const res = await fetch('/admin/landing/unggah', {
      method: 'POST',
      body,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        Accept: 'application/json',
      },
    })
    if (!res.ok) throw new Error('upload gagal')
    set(key, (await res.json()).url)
  } catch {
    alert('Gagal mengunggah gambar. Coba lagi.')
  } finally {
    uploading.value[key] = false
    event.target.value = ''
  }
}

const addItem = (field) => {
  const list = get(field.key) ?? []
  if (field.max && list.length >= field.max) return
  set(field.key, [...list, field.make()])
}

const removeItem = (field, i) => {
  const list = [...(get(field.key) ?? [])]
  list.splice(i, 1)
  set(field.key, list)
}

const toggleProduct = (key, id) => {
  const list = get(key) ?? []
  set(key, list.includes(id) ? list.filter((x) => x !== id) : [...list, id])
}
</script>

<template>
  <div class="space-y-5">
    <div v-for="field in fields" :key="field.key">
      <label class="field-label">{{ field.label }}</label>
      <p v-if="field.hint" class="mb-1.5 text-[0.72rem] text-muted">{{ field.hint }}</p>

      <!-- text -->
      <input
        v-if="field.type === 'text'"
        :value="get(field.key)" class="field"
        @input="set(field.key, $event.target.value)"
      />

      <!-- textarea -->
      <textarea
        v-else-if="field.type === 'textarea'"
        :value="get(field.key)" rows="3" class="field"
        @input="set(field.key, $event.target.value)"
      />

      <!-- icon -->
      <select
        v-else-if="field.type === 'icon'"
        :value="get(field.key)" class="field"
        @change="set(field.key, $event.target.value)"
      >
        <option v-for="icon in ICONS" :key="icon" :value="icon">{{ icon }}</option>
      </select>

      <!-- image -->
      <div v-else-if="field.type === 'image'" class="flex items-center gap-4">
        <div class="h-20 w-28 flex-none overflow-hidden border border-line bg-sand">
          <img v-if="get(field.key)" :src="get(field.key)" alt="" class="h-full w-full object-cover" />
        </div>
        <div class="flex items-center gap-3">
          <label class="cursor-pointer border border-line px-3 py-2 text-[0.78rem] text-forest transition hover:bg-sand/40">
            {{ uploading[field.key] ? 'Mengunggah…' : 'Pilih foto' }}
            <input type="file" accept="image/*" class="sr-only" @change="onImage(field.key, $event)" />
          </label>
          <button
            v-if="get(field.key)" type="button"
            class="text-[0.78rem] text-danger" @click="set(field.key, '')"
          >Hapus</button>
        </div>
      </div>

      <!-- products -->
      <div v-else-if="field.type === 'products'" class="max-h-56 overflow-y-auto border border-line">
        <label
          v-for="product in products" :key="product.id"
          class="flex cursor-pointer items-center gap-3 border-b border-line px-3 py-2 text-[0.8rem] last:border-b-0 hover:bg-sand/30"
        >
          <input
            type="checkbox" :checked="(get(field.key) ?? []).includes(product.id)"
            @change="toggleProduct(field.key, product.id)"
          />
          <span class="h-8 w-8 flex-none overflow-hidden border border-line bg-sand">
            <img v-if="product.image" :src="product.image" alt="" class="h-full w-full object-cover" />
          </span>
          <span class="flex-1 truncate text-forest">{{ product.name }}</span>
          <span class="text-muted">{{ (get(field.key) ?? []).indexOf(product.id) + 1 || '' }}</span>
        </label>
        <p v-if="!products.length" class="px-3 py-4 text-[0.78rem] text-muted">Belum ada produk aktif.</p>
      </div>

      <!-- list / repeater -->
      <div v-else-if="field.type === 'list'" class="space-y-3">
        <div
          v-for="(item, i) in get(field.key) ?? []" :key="i"
          class="border border-line bg-surface p-4"
        >
          <div class="mb-3 flex items-center justify-between">
            <span class="text-[0.72rem] uppercase tracking-wider text-muted">#{{ i + 1 }}</span>
            <button type="button" class="text-danger" :aria-label="`Hapus item ${i + 1}`" @click="removeItem(field, i)">
              <Trash2 class="h-4 w-4" />
            </button>
          </div>
          <BlockFields
            :fields="field.fields" :model="item" :products="products"
            :errors="errors" :error-prefix="`${errorPrefix}.${field.key}.${i}`"
          />
        </div>
        <button
          type="button"
          class="flex items-center gap-2 border border-dashed border-line px-3 py-2 text-[0.78rem] text-forest transition hover:bg-sand/40 disabled:opacity-40"
          :disabled="field.max && (get(field.key) ?? []).length >= field.max"
          @click="addItem(field)"
        >
          <Plus class="h-3.5 w-3.5" /> Tambah {{ field.label.toLowerCase() }}
        </button>
      </div>

      <p v-if="errorFor(field.key)" class="mt-1.5 text-[0.75rem] text-danger">{{ errorFor(field.key) }}</p>
    </div>
  </div>
</template>
