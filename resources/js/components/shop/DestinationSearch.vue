<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: null },
  initialLabel: { type: String, default: '' },
  placeholder: { type: String, default: 'Cari kota, kecamatan, atau kode pos…' },
})
const emit = defineEmits(['update:modelValue', 'select'])

const query = ref('')
const results = ref([])
const loading = ref(false)
const open = ref(false)
const selectedLabel = ref(props.initialLabel)
let searchTimer = null

watch(query, (q) => {
  clearTimeout(searchTimer)
  const term = q.trim()
  if (term.length < 3) { results.value = []; loading.value = false; return }
  loading.value = true
  searchTimer = setTimeout(async () => {
    try {
      const res = await fetch(`/shipping/destinations?q=${encodeURIComponent(term)}`)
      results.value = res.ok ? await res.json() : []
    } catch {
      results.value = []
    } finally {
      loading.value = false
    }
  }, 300)
})

const choose = (destination) => {
  selectedLabel.value = destination.label
  open.value = false
  query.value = ''
  results.value = []
  emit('update:modelValue', String(destination.id))
  emit('select', destination)
}

const reset = () => {
  selectedLabel.value = ''
  query.value = ''
  emit('update:modelValue', null)
  emit('select', null)
  open.value = true
}
</script>

<template>
  <div class="relative">
    <div v-if="selectedLabel && !open" class="field flex items-center justify-between gap-3">
      <span class="truncate text-forest">{{ selectedLabel }}</span>
      <button type="button" class="flex-none text-[0.72rem] text-muted underline transition hover:text-forest" @click="reset">Ganti</button>
    </div>
    <template v-else>
      <input v-model="query" :placeholder="placeholder" class="field" @focus="open = true" />
      <ul v-if="open && (loading || results.length || query.trim().length >= 3)" class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto border border-line bg-surface shadow-sm">
        <li v-if="loading" class="px-3 py-2 text-[0.78rem] text-muted">Mencari kota…</li>
        <li v-for="d in results" :key="d.id">
          <button type="button" class="block w-full px-3 py-2 text-left text-[0.82rem] text-forest transition hover:bg-ivory" @click="choose(d)">
            {{ d.label }}
          </button>
        </li>
        <li v-if="!loading && query.trim().length >= 3 && !results.length" class="px-3 py-2 text-[0.78rem] text-muted">Tidak ada kota yang cocok, coba kata kunci lain.</li>
      </ul>
    </template>
  </div>
</template>
