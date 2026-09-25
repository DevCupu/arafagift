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
const failed = ref(false)
const failureMessage = ref('')
let searchTimer = null
let searchController = null
let searchSeq = 0

const search = async (term, seq) => {
  loading.value = true
  failed.value = false
  const controller = new AbortController()
  searchController = controller
  try {
    const res = await fetch(`/api/shipping/destinations?search=${encodeURIComponent(term)}`, {
      headers: { Accept: 'application/json' },
      signal: controller.signal,
    })
    const payload = res.ok ? await res.json() : null
    if (seq !== searchSeq) return
    if (!res.ok || !payload?.success || !Array.isArray(payload.data)) {
      results.value = []
      failed.value = true
      failureMessage.value = payload?.message || 'Gagal memuat daftar kota. Coba lagi.'
      return
    }
    results.value = payload.data
  } catch (error) {
    if (seq !== searchSeq || error?.name === 'AbortError') return
    results.value = []
    failed.value = true
    failureMessage.value = 'Gagal memuat daftar kota. Coba lagi.'
  } finally {
    if (seq === searchSeq) loading.value = false
  }
}

const retry = () => {
  searchSeq++
  search(query.value.trim(), searchSeq)
}

watch(query, (q) => {
  clearTimeout(searchTimer)
  const term = q.trim()
  searchSeq++
  const seq = searchSeq
  searchController?.abort()
  results.value = []
  failed.value = false
  if (term.length < 3) {
    loading.value = false
    return
  }
  loading.value = true
  searchTimer = setTimeout(() => search(term, seq), 700)
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
        <li v-if="loading" aria-busy="true" class="px-3 py-3">
          <div class="animate-pulse space-y-2">
            <div class="h-3 w-3/4 rounded bg-sand/60" />
            <div class="h-3 w-1/2 rounded bg-sand/60" />
            <div class="h-3 w-2/3 rounded bg-sand/60" />
          </div>
        </li>
        <li v-for="d in results" :key="d.id">
          <button type="button" class="block w-full px-3 py-2 text-left text-[0.82rem] text-forest transition hover:bg-ivory" @click="choose(d)">
            {{ d.label }}
          </button>
        </li>
        <li v-if="failed" class="px-3 py-2">
          <p class="text-[0.78rem] leading-relaxed text-muted">{{ failureMessage }}</p>
          <p class="mt-1.5 text-[0.72rem] leading-relaxed text-muted">Masih belum ketemu? Anda juga bisa memasukkan kota secara manual pada langkah berikutnya.</p>
          <button type="button" class="mt-1.5 text-[0.74rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive active:translate-y-px" @click="retry">
            Coba lagi
          </button>
        </li>
        <li v-if="!loading && !failed && query.trim().length >= 3 && !results.length" class="px-3 py-2 text-[0.78rem] leading-relaxed text-muted">Tidak ada kota yang cocok, coba kata kunci lain. Anda juga bisa memasukkan kota secara manual pada langkah berikutnya.</li>
      </ul>
    </template>
  </div>
</template>
