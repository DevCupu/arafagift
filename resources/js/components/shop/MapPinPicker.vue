<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import { Crosshair, MapPin } from 'lucide-vue-next'

// Titik peta hanya pelengkap untuk kurir; alamat teks tetap sumber utama.
// Pin awal diambil dari GPS (kalau diizinkan), lalu bisa digeser / diketuk di peta.
const model = defineModel({ type: Object, default: null })

const el = ref(null)
const status = ref('')
const locating = ref(false)
let map = null
let marker = null
let L = null

const DEFAULT_CENTER = [-2.5, 118] // tengah Indonesia

const emit = defineEmits(['address'])

// Reverse-geocode (Nominatim/OSM) tiap pin berpindah; hasilnya cuma "saran" yang diisikan ke form.
let geoSeq = 0
const reverseGeocode = async (lat, lng) => {
  const seq = ++geoSeq
  status.value = 'Membaca alamat dari titik ini…'
  try {
    const res = await fetch(
      `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&accept-language=id&lat=${lat}&lon=${lng}`,
      { headers: { Accept: 'application/json' } },
    )
    if (!res.ok) throw new Error()
    const data = await res.json()
    if (seq !== geoSeq) return
    emit('address', data.address ?? {})
    status.value = 'Alamat terisi dari titik ini. Periksa lagi, geser pin kalau kurang tepat.'
  } catch {
    if (seq === geoSeq) status.value = 'Alamat tidak bisa dibaca otomatis. Isi manual di atas.'
  }
}

const setPoint = (lat, lng, zoom) => {
  const point = [lat, lng]
  if (!marker) {
    marker = L.marker(point, { draggable: true }).addTo(map)
    marker.on('dragend', () => {
      const p = marker.getLatLng()
      setPoint(p.lat, p.lng)
    })
  } else {
    marker.setLatLng(point)
  }
  if (zoom) map.setView(point, zoom)
  model.value = { lat: +lat.toFixed(6), lng: +lng.toFixed(6) }
  reverseGeocode(lat, lng)
}

const locate = () => {
  if (!navigator.geolocation) {
    status.value = 'Browser tidak mendukung lokasi. Ketuk peta untuk menaruh pin.'
    return
  }
  locating.value = true
  status.value = 'Mencari lokasi Anda…'
  navigator.geolocation.getCurrentPosition(
    (pos) => {
      locating.value = false
      setPoint(pos.coords.latitude, pos.coords.longitude, 17)
    },
    () => {
      locating.value = false
      status.value = 'Lokasi tidak diizinkan. Ketuk peta untuk menaruh pin manual.'
    },
    { enableHighAccuracy: true, timeout: 10000 },
  )
}

const clear = () => {
  geoSeq++
  if (marker) { marker.remove(); marker = null }
  model.value = null
  status.value = ''
}

onMounted(async () => {
  const mod = await import('leaflet')
  await import('leaflet/dist/leaflet.css')
  L = mod.default ?? mod
  // Ikon bawaan Leaflet bergantung pada path CSS yang tidak ikut di-bundle: pakai CDN.
  const base = 'https://unpkg.com/leaflet@1.9.4/dist/images/'
  L.Icon.Default.prototype.options.iconUrl = `${base}marker-icon.png`
  L.Icon.Default.prototype.options.iconRetinaUrl = `${base}marker-icon-2x.png`
  L.Icon.Default.prototype.options.shadowUrl = `${base}marker-shadow.png`
  L.Icon.Default.prototype.options.iconSize = [25, 41]
  L.Icon.Default.prototype.options.iconAnchor = [12, 41]

  map = L.map(el.value, { scrollWheelZoom: false }).setView(
    model.value ? [model.value.lat, model.value.lng] : DEFAULT_CENTER,
    model.value ? 17 : 4,
  )
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap',
  }).addTo(map)
  map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng))
  // Pin lama (modal dibuka ulang): tampilkan saja, jangan menimpa isian alamat.
  if (model.value) {
    marker = L.marker([model.value.lat, model.value.lng], { draggable: true }).addTo(map)
    marker.on('dragend', () => setPoint(marker.getLatLng().lat, marker.getLatLng().lng))
  }
})

onBeforeUnmount(() => map?.remove())
</script>

<template>
  <div>
    <div class="flex flex-wrap items-center justify-between gap-2">
      <span class="field-label !mb-0 flex items-center gap-1.5"><MapPin class="h-3.5 w-3.5 text-gold" :stroke-width="1.5" /> Titik lokasi (opsional)</span>
      <div class="flex items-center gap-3">
        <button type="button" class="flex items-center gap-1.5 text-[0.78rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive disabled:opacity-50" :disabled="locating" @click="locate">
          <Crosshair class="h-3.5 w-3.5" :stroke-width="1.7" /> Gunakan lokasi saya
        </button>
        <button v-if="model" type="button" class="text-[0.72rem] text-muted underline transition hover:text-forest" @click="clear">Hapus pin</button>
      </div>
    </div>
    <div ref="el" class="mt-2 h-56 w-full border border-line bg-ivory" />
    <p class="mt-1.5 text-[0.72rem] leading-relaxed text-muted">
      {{ status || 'Bantu kurir menemukan rumah Anda: nyalakan GPS lalu geser pin, atau ketuk peta. Alamat tertulis tetap yang utama.' }}
    </p>
  </div>
</template>
