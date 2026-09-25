<script>
import BareLayout from '@/layouts/BareLayout.vue'
export default { layout: BareLayout }
</script>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { AlertCircle, ArrowLeft, Check, ChevronUp, Gift, LockKeyhole, MapPin, MessageCircle, Truck, X } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import DestinationSearch from '@/components/shop/DestinationSearch.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'
import { statusMeta } from '@/data/admin'

const page = usePage()
const store = computed(() => page.props.store)

const cart = useCart()

// Tamu boleh melihat & mengisi halaman checkout dulu; login baru diminta saat "Buat Pesanan".
// Isian disimpan sementara biar tidak hilang saat pindah ke halaman login.
const CHECKOUT_DRAFT_KEY = 'arafahgift.checkout.draft'
const isGuest = computed(() => !page.props.auth?.user)
const user = computed(() => page.props.auth?.user)
const accountHasPhone = computed(() => !!user.value?.phone?.trim())

const redirectToLogin = () => {
  try {
    sessionStorage.setItem(CHECKOUT_DRAFT_KEY, JSON.stringify({ form: { ...form }, manualCity: manualCity.value }))
  } catch { /* mode privat: abaikan */ }
  window.location.assign(`/login?redirect=${encodeURIComponent('/checkout')}`)
}

const steps = [
  { id: 1, label: 'Alamat' },
  { id: 2, label: 'Tinjau' },
]
const step = ref(1)
const errors = reactive({})
// Error dari server (abort 422 / field tanpa input langsung di halaman) ditampilkan sebagai banner,
// bukan alert() — pesan asli seperti "Opsi pengiriman tidak lagi tersedia" tidak boleh hilang.
const serverMessage = ref(null)
const serverBanner = ref([])
const clearServerFeedback = () => {
  serverMessage.value = null
  serverBanner.value = []
}
const addressModalOpen = ref(false)
const openAddressModal = () => { addressModalOpen.value = true }
const closeAddressModal = () => { addressModalOpen.value = false }

watch(addressModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

const saveAddress = () => {
  delete errors.address
  delete errors.destination
  if (form.street.trim().length < 8) errors.address = 'Tulis nama jalan dan nomor rumah.'
  if (manualCity.value) {
    if (!form.city.trim()) errors.destination = 'Isi kota atau kabupaten.'
  } else if (!form.destinationId) {
    errors.destination = 'Cari dan pilih kota/kecamatan tujuan.'
  }
  if (Object.keys(errors).length) return
  addressModalOpen.value = false
}

const summaryOpen = ref(false)
watch(summaryOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

const form = reactive({
  name: '', email: '', phone: '',
  street: '', rt: '', rw: '', landmark: '', city: '', province: '', postal: '', destinationId: null,
  giftMessage: '', note: '', hideInvoice: true,
})

// Digabung jadi satu baris alamat siap-kirim (WhatsApp, ringkasan, payload) dari kolom terpisah.
// dipecah biar user lebih terpandu isi detailnya, tapi sisi server/WA tetap terima satu string alamat.
const fullAddress = computed(() => {
  const parts = [form.street.trim()]
  const neighborhood = [
    form.rt.trim() ? `RT ${form.rt.trim()}` : '',
    form.rw.trim() ? `RW ${form.rw.trim()}` : '',
  ].filter(Boolean).join(' / ')
  if (neighborhood) parts.push(neighborhood)
  const base = parts.filter(Boolean).join(', ')
  return form.landmark.trim() ? `${base} (Patokan: ${form.landmark.trim()})` : base
})

// ponytail: manual fallback exists only so a total RajaOngkir outage can't block checkout entirely.
const manualCity = ref(false)
const toggleManualCity = () => {
  manualCity.value = !manualCity.value
  form.destinationId = null
  form.city = ''
  form.province = ''
  form.postal = ''
  shippingOptions.value = []
  selectedShipping.value = null
  shippingUnavailable.value = manualCity.value
  shippingError.value = null
}

const validate = (current) => {
  clearServerFeedback()
  Object.keys(errors).forEach((k) => delete errors[k])
  if (current === 1) {
    if (form.street.trim().length < 8) errors.address = 'Tulis nama jalan dan nomor rumah.'
    if (manualCity.value) {
      if (!form.city.trim()) errors.destination = 'Isi kota atau kabupaten.'
    } else if (!form.destinationId) {
      errors.destination = 'Cari dan pilih kota/kecamatan tujuan.'
    } else if (!shippingUnavailable.value && !hasFreeShipping.value && !selectedShipping.value) {
      errors.destination = 'Tunggu pilihan kurir termuat, atau pilih salah satu.'
    }
  }
  if (current === 2) {
    if (form.name.trim().length < 3) errors.name = 'Tulis nama lengkap penerima pesanan.'
    if (form.phone.replace(/\D/g, '').length < 9) errors.phone = 'Nomor WhatsApp minimal 9 angka.'
  }
  return Object.keys(errors).length === 0
}

const next = () => { if (validate(step.value)) step.value = Math.min(steps.length, step.value + 1) }
const back = () => { step.value = Math.max(1, step.value - 1) }

const freeShippingByCity = computed(() =>
  store.value.freeShippingCities.some((c) => form.city.trim().toLowerCase().includes(c.toLowerCase())),
)
const freeShippingByAmount = computed(() =>
  store.value.freeShippingFrom > 0 && cart.subtotal.value >= store.value.freeShippingFrom,
)
const hasFreeShipping = computed(() => freeShippingByCity.value || freeShippingByAmount.value)

const shippingOptions = ref([])
const shippingLoading = ref(false)
const shippingUnavailable = ref(false)
const shippingError = ref(null)
const selectedShipping = ref(null)
const shippingWeight = ref(null)

const cargoMarkers = ['cargo', 'trucking', 'jtr', 'gokil']
const isCargoService = (option) => {
  const searchable = `${option.service ?? ''} ${option.description ?? ''}`.toLowerCase()
  return cargoMarkers.some((marker) => searchable.includes(marker))
}

const eligibleShippingOptions = computed(() => shippingOptions.value
  .filter((option) => !(shippingWeight.value < 10000 && isCargoService(option)))
  .slice()
  .sort((a, b) => Number(a.cost) - Number(b.cost)))

const shippingGroups = computed(() => {
  const groups = new Map()
  eligibleShippingOptions.value.forEach((option) => {
    if (!groups.has(option.courier)) {
      groups.set(option.courier, {
        code: option.courier,
        name: option.courier_name || option.courier.toUpperCase(),
        options: [],
      })
    }
    groups.get(option.courier).options.push(option)
  })
  return [...groups.values()]
})

const cheapestShippingCost = computed(() => eligibleShippingOptions.value[0]?.cost ?? null)
const etdInDays = (etd) => {
  const match = String(etd ?? '').match(/\d+/)
  return match ? Number(match[0]) : Number.POSITIVE_INFINITY
}
const fastestShippingDays = computed(() => {
  const availableEstimates = eligibleShippingOptions.value
    .map((option) => etdInDays(option.etd))
    .filter(Number.isFinite)
  return availableEstimates.length ? Math.min(...availableEstimates) : null
})
const shippingBadges = (option) => {
  const badges = []
  if (Number(option.cost) === Number(cheapestShippingCost.value)) badges.push('Termurah')
  if (fastestShippingDays.value !== null && etdInDays(option.etd) === fastestShippingDays.value) badges.push('Tercepat')
  return badges
}

const fetchShipping = async () => {
  if (!form.destinationId) return
  shippingLoading.value = true
  shippingUnavailable.value = false
  shippingError.value = null
  selectedShipping.value = null
  shippingOptions.value = []
  shippingWeight.value = null
  try {
    const res = await fetch('/shipping/cost', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        destination_id: form.destinationId,
        items: cart.items.value.map((i) => ({ id: i.id, qty: i.qty })),
      }),
    })
    if (!res.ok) {
      const data = await res.json().catch(() => null)
      if (res.status >= 400 && res.status < 500 && data?.errors) {
        shippingError.value = 'Produk atau jumlah di keranjang tidak valid untuk dihitung ongkirnya. Periksa kembali keranjang Anda.'
      } else {
        shippingUnavailable.value = true
      }
      return
    }
    const data = await res.json()
    if (!data.available || !data.options?.length) {
      shippingUnavailable.value = true
      return
    }
    shippingOptions.value = data.options
    shippingWeight.value = data.weight
    if (!eligibleShippingOptions.value.length) {
      shippingUnavailable.value = true
      return
    }
    selectedShipping.value = eligibleShippingOptions.value[0]
  } catch {
    shippingUnavailable.value = true
  } finally {
    shippingLoading.value = false
  }
}

const onDestinationSelect = (destination) => {
  if (!destination) {
    form.destinationId = null
    form.city = ''
    form.province = ''
    form.postal = ''
    shippingOptions.value = []
    selectedShipping.value = null
    shippingUnavailable.value = false
    shippingError.value = null
    return
  }
  form.city = destination.city
  form.province = destination.province
  form.postal = destination.zip || ''
  shippingOptions.value = []
  selectedShipping.value = null
  shippingUnavailable.value = false
  shippingError.value = null
  // Kota ini sudah pasti gratis ongkir, jadi tidak perlu meminta tarif ke RajaOngkir.
  if (hasFreeShipping.value) return
  fetchShipping()
}

const etdText = (etd) => {
  const value = String(etd ?? '').trim()
  if (!value || value === '-') return 'Estimasi belum tersedia'
  return `Estimasi ${value.replace(/\bdays?\b/gi, 'hari')}`
}
const weightText = (grams) => `${(grams / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} kg`

const shippingCostDisplay = computed(() => {
  if (hasFreeShipping.value) return 0
  return selectedShipping.value ? selectedShipping.value.cost : null
})
const grandTotal = computed(() => cart.subtotal.value + (shippingCostDisplay.value ?? 0))

const buildWaMessage = (order) => {
  const subtotal = order.items.reduce((sum, i) => sum + i.price * i.qty, 0)
  const shippingLine = order.shipping.courier !== 'Belum ditentukan'
    ? `Ongkir: ${order.shipping.courier.toUpperCase()} ${order.shipping.method}, ${formatIDR(order.shipping.cost)}${order.shipping.etd ? ` (${etdText(order.shipping.etd)})` : ''}`
    : `Ongkir: ${hasFreeShipping.value ? 'Gratis' : 'akan dikonfirmasi ya'}`
  const lines = [
    `Halo ArafahGift, saya mau pesan:`,
    '',
    ...order.items.map((i) => `• ${i.name} × ${i.qty}: ${formatIDR(i.price * i.qty)}`),
    '',
    `Subtotal: ${formatIDR(subtotal)}`,
    shippingLine,
    '',
    `Nomor pesanan: ${order.id}`,
    `Nama: ${form.name}`,
    `WhatsApp: ${form.phone}`,
    form.email ? `Email: ${form.email}` : null,
    `Alamat: ${fullAddress.value}, ${form.city}${form.postal ? ` ${form.postal}` : ''}${form.province ? `, ${form.province}` : ''}`,
    form.giftMessage ? `Kartu ucapan: "${form.giftMessage}"` : null,
    form.hideInvoice ? `Tolong sembunyikan nota harga di dalam paket.` : null,
  ]
  return lines.filter((l) => l !== null).join('\n')
}

const submitting = ref(false)
const placedOrder = ref(null)

const placeOrder = async () => {
  if (isGuest.value) return redirectToLogin()
  clearServerFeedback()
  if (!validate(1) || !validate(2)) return
  if (submitting.value) return
  submitting.value = true

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
    const res = await fetch('/checkout', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        name: form.name,
        phone: form.phone,
        email: form.email || null,
        address: fullAddress.value,
        city: form.city,
        province: form.province || null,
        postal: form.postal || null,
        destination_id: form.destinationId || null,
        courier: selectedShipping.value?.courier || null,
        service: selectedShipping.value?.service || null,
        giftMessage: form.giftMessage || null,
        note: form.note || null,
        hideInvoice: form.hideInvoice,
        items: cart.items.value.map((i) => ({ id: i.id, qty: i.qty })),
      }),
    })

    if (!res.ok) {
      const data = await res.json().catch(() => null)
      serverMessage.value = null
      if (data?.errors) {
        let needsAddressStep = false
        Object.entries(data.errors).forEach(([key, msgs]) => {
          const message = Array.isArray(msgs) ? msgs[0] : msgs
          if (!message) return
          if (key === 'address') {
            errors.address = message
            needsAddressStep = true
          } else if (key === 'city' || key === 'destination_id' || key === 'postal' || key === 'province') {
            errors.destination = message
            needsAddressStep = true
          } else if (key === 'name' || key === 'phone') {
            errors[key] = message
          } else {
            serverBanner.value.push(message)
          }
        })
        if (needsAddressStep && step.value !== 1) step.value = 1
      } else if (data?.message) {
        serverMessage.value = data.message
      } else {
        serverMessage.value = 'Gagal menyimpan pesanan. Coba lagi.'
      }
      return
    }

    placedOrder.value = await res.json()
    cart.clear()
    clearServerFeedback()
    try { sessionStorage.removeItem(CHECKOUT_DRAFT_KEY) } catch { /* abaikan */ }
  } catch {
    serverMessage.value = 'Terjadi kesalahan jaringan. Coba lagi.'
  } finally {
    submitting.value = false
  }
}

const confirmViaWhatsapp = () => {
  const number = (store.value.whatsapp || '').replace(/\D/g, '')
  const url = `https://wa.me/${number}?text=${encodeURIComponent(buildWaMessage(placedOrder.value))}`
  window.open(url, '_blank', 'noopener')
}

onMounted(() => {
  const raw = sessionStorage.getItem(CHECKOUT_DRAFT_KEY)
  if (raw) {
    try {
      const draft = JSON.parse(raw)
      if (draft?.form) Object.assign(form, draft.form)
      manualCity.value = !!draft?.manualCity
    } catch { /* draft korup: abaikan */ }
    sessionStorage.removeItem(CHECKOUT_DRAFT_KEY)
  }
  // Data pemesan dari akun mengisi field yang masih kosong saja — draft (input user)
  // tetap menang biar tidak menimpa yang sudah diketik sebelum pindah ke halaman login.
  if (user.value) {
    if (!form.name) form.name = user.value.name ?? ''
    if (!form.phone) form.phone = user.value.phone ?? ''
    if (!form.email) form.email = user.value.email ?? ''
  }
  if (!manualCity.value && form.destinationId && !hasFreeShipping.value) fetchShipping()
  else shippingUnavailable.value = manualCity.value
})
</script>

<template>
  <Head title="Checkout">
    <meta name="robots" content="noindex,follow" />
  </Head>
  <div class="min-h-[100dvh] bg-ivory">
    <div v-if="placedOrder" class="mx-auto max-w-xl px-5 py-16 sm:px-10">
      <BrandLogo size="sm" />
      <div class="mt-8 border border-line bg-surface p-8 text-center">
        <span class="grid h-12 w-12 place-items-center rounded-full bg-forest text-ivory mx-auto"><Check class="h-6 w-6" /></span>
        <h1 class="mt-5 text-[1.9rem] leading-none">Pesanan berhasil dibuat</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Nomor pesanan Anda</p>
        <p class="mt-1 font-display text-3xl text-forest">{{ placedOrder.id }}</p>
        <p class="mt-2 text-[0.78rem] text-muted">Status: {{ statusMeta(placedOrder.status).label }}</p>
      </div>

      <dl class="mt-6 divide-y divide-line border-y border-line text-[0.87rem]">
        <div v-for="(it, i) in placedOrder.items" :key="i" class="flex justify-between gap-6 py-3">
          <dt class="text-muted">{{ it.name }} × {{ it.qty }}</dt>
          <dd class="text-forest">{{ formatIDR(it.price * it.qty) }}</dd>
        </div>
      </dl>

      <div class="mt-8 flex flex-col gap-3">
        <AppButton size="lg" @click="confirmViaWhatsapp">
          <template #icon><MessageCircle class="h-4 w-4" /></template>
          Konfirmasi via WhatsApp
        </AppButton>
        <Link href="/lacak-pesanan" class="text-center text-[0.8rem] text-muted underline transition hover:text-forest">
          Lacak status pesanan ini nanti
        </Link>
        <Link href="/" class="text-center text-[0.8rem] text-muted transition hover:text-forest">
          Kembali ke beranda
        </Link>
      </div>
    </div>

    <div v-else-if="cart.items.value.length" class="mx-auto grid max-w-6xl lg:grid-cols-[1.2fr_1fr]">
      <!-- Form -->
      <div class="px-5 py-10 sm:px-10 lg:py-14">
        <div class="flex items-center justify-between">
          <BrandLogo size="sm" />
          <Link href="/koleksi" class="flex items-center gap-1.5 text-[0.78rem] text-muted transition hover:text-forest">
            <ArrowLeft class="h-3.5 w-3.5" /> Kembali berbelanja
          </Link>
        </div>

        <div v-if="serverMessage || serverBanner.length" role="alert" class="mt-6 flex items-start gap-3 border border-danger/40 bg-danger/[0.07] px-4 py-3.5 text-[0.82rem] leading-relaxed text-ink">
          <AlertCircle class="mt-0.5 h-4 w-4 flex-none text-danger" :stroke-width="1.5" />
          <p>
            <span v-if="serverMessage">{{ serverMessage }}</span>
            <template v-for="(msg, i) in serverBanner" :key="i">
              <span v-if="i > 0" class="mt-1 block">{{ msg }}</span>
              <span v-else>{{ msg }}</span>
            </template>
          </p>
        </div>

        <!-- Stepper: urutan checkout benar-benar berurutan -->
        <ol class="mt-10 flex items-center gap-3" aria-label="Langkah checkout">
          <li v-for="s in steps" :key="s.id" class="flex flex-1 items-center gap-3">
            <span
              class="grid h-8 w-8 flex-none place-items-center rounded-full border font-display text-[0.78rem] transition"
              :class="step > s.id ? 'border-forest bg-forest text-ivory'
                : step === s.id ? 'border-gold bg-gold/15 text-forest ring-1 ring-gold/[0.4]' : 'border-line bg-surface text-muted'"
            >
              <Check v-if="step > s.id" class="h-3.5 w-3.5" :stroke-width="2" />
              <template v-else>{{ s.id }}</template>
            </span>
            <span class="hidden text-[0.75rem] font-medium tracking-wide sm:block" :class="step >= s.id ? 'text-forest' : 'text-muted'">{{ s.label }}</span>
            <span v-if="s.id < steps.length" class="h-px flex-1 bg-line" :class="step > s.id ? '!bg-forest/40' : ''" />
          </li>
        </ol>

        <!-- 1. Alamat -->
        <section v-if="step === 1" class="mt-10">
          <p class="eyebrow">Langkah 1 dari 2</p>
          <h1 class="mt-4 text-[2rem] leading-[1.05] tracking-[-0.02em] sm:text-[2.2rem]">Alamat pengiriman</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Cari kota tujuan untuk melihat pilihan kurir dan ongkirnya.</p>

          <!-- Nomor WhatsApp belum terisi di akun (diperlukan untuk konfirmasi) -->
          <div v-if="user && !accountHasPhone" class="mt-8 border border-gold/40 bg-gold/[0.07] p-4 sm:p-5">
            <label class="field-label" for="phone">Nomor WhatsApp (untuk konfirmasi)</label>
            <input id="phone" v-model="form.phone" class="field" placeholder="08xx xxxx xxxx" :aria-invalid="!!errors.phone" />
            <p class="mt-1.5 text-[0.72rem] leading-relaxed text-muted">Belum ada di akun Anda. Dipakai untuk konfirmasi pesanan via WhatsApp.</p>
            <p v-if="errors.phone" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.phone }}</p>
          </div>

          <div class="mt-8">
            <!-- Belum ada alamat: buka modal input -->
            <button
              v-if="!form.street.trim()"
              type="button"
              class="flex w-full flex-col items-center gap-1 border border-dashed border-forest/30 bg-surface p-10 text-center transition hover:border-forest/60 hover:bg-ivory sm:p-12"
              @click="openAddressModal"
            >
              <span class="grid h-12 w-12 place-items-center rounded-full border border-gold/40 bg-gold/[0.08]">
                <MapPin class="h-5 w-5 text-gold" :stroke-width="1.5" />
              </span>
              <span class="mt-4 font-display text-xl text-forest">Tambah alamat pengiriman</span>
              <span class="mt-1 text-[0.8rem] text-muted">Dipakai untuk menghitung ongkir dan mengirim pesanan.</span>
            </button>

            <div v-else class="border border-line bg-surface p-5 sm:p-6">
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-2.5">
                  <MapPin class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
                  <h2 class="font-display text-lg text-forest">Alamat pengiriman</h2>
                </div>
                <button type="button" class="flex-none text-[0.78rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive" @click="openAddressModal">
                  Ubah
                </button>
              </div>
              <p class="mt-3 text-[0.9rem] leading-relaxed text-forest">{{ fullAddress }}</p>
              <p class="mt-0.5 text-[0.8rem] text-muted">{{ form.city }}{{ form.postal ? ` ${form.postal}` : '' }}{{ form.province ? `, ${form.province}` : '' }}</p>
              <p v-if="errors.address || errors.destination" class="mt-3 text-[0.78rem] text-danger">
                {{ errors.address || errors.destination }}
              </p>
            </div>

            <!-- Gratis ongkir & pilihan kurir (tampil setelah alamat tersimpan) -->
            <div v-if="form.street.trim()" class="mt-6">

            <p v-if="hasFreeShipping" class="flex items-center gap-2 text-[0.8rem] text-olive">
              <Truck class="h-4 w-4" :stroke-width="1.5" /> Gratis ongkir untuk pesanan ini.
            </p>
            <template v-else-if="!manualCity && form.destinationId">
              <div v-if="shippingLoading" class="border border-line bg-ivory/45 p-4" aria-live="polite">
                <p class="text-[0.8rem] font-medium text-forest">Mencari pilihan pengiriman</p>
                <div class="mt-3 space-y-2.5">
                  <span v-for="i in 3" :key="i" class="motion-safe:animate-pulse block h-14 bg-line/40" />
                </div>
              </div>
              <div v-else-if="shippingUnavailable" class="border border-line bg-ivory/45 p-4 text-[0.8rem] leading-relaxed text-muted">
                <p>Ongkos kirim belum bisa dihitung otomatis untuk tujuan ini. Kami dapat mengonfirmasinya melalui WhatsApp.</p>
                <button type="button" class="mt-2 font-semibold text-forest underline underline-offset-4 transition hover:text-olive active:translate-y-px" @click="fetchShipping">
                  Coba hitung lagi
                </button>
              </div>
              <div v-else-if="shippingError" class="border border-danger/35 bg-danger/[0.06] p-4 text-[0.8rem] leading-relaxed text-ink">
                <p>{{ shippingError }}</p>
                <Link href="/koleksi" class="mt-2 inline-block font-semibold text-forest underline underline-offset-4 transition hover:text-olive">Periksa keranjang Anda</Link>
              </div>
              <div v-else-if="shippingGroups.length" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-2">
                  <div>
                    <h2 class="font-display text-xl text-forest">Pilihan pengiriman</h2>
                    <p class="mt-1 text-[0.72rem] text-muted">Layanan kargo disembunyikan untuk paket ringan.</p>
                  </div>
                  <p v-if="shippingWeight" class="text-[0.72rem] text-muted">Berat {{ weightText(shippingWeight) }}</p>
                </div>

                <section v-for="group in shippingGroups" :key="group.code" class="overflow-hidden border border-line bg-surface">
                  <header class="flex items-center justify-between gap-4 bg-ivory/55 px-4 py-3">
                    <div class="flex min-w-0 items-center gap-3">
                      <span class="grid h-9 w-9 flex-none place-items-center border border-line bg-surface text-[0.68rem] font-bold tracking-wide text-forest">
                        {{ group.code.toUpperCase().slice(0, 3) }}
                      </span>
                      <div class="min-w-0">
                        <h3 class="truncate text-[0.82rem] font-semibold text-forest">{{ group.name }}</h3>
                        <p class="text-[0.68rem] text-muted">{{ group.options.length }} layanan tersedia</p>
                      </div>
                    </div>
                  </header>

                  <div>
                    <label
                      v-for="opt in group.options"
                      :key="`${opt.courier}-${opt.service}-${opt.cost}`"
                      class="flex cursor-pointer items-start gap-3 border-t border-line px-4 py-3.5 transition active:translate-y-px"
                      :class="selectedShipping === opt ? 'bg-forest/[0.055]' : 'bg-surface hover:bg-ivory/45'"
                    >
                      <input
                        type="radio"
                        name="shipping-option"
                        class="sr-only"
                        :checked="selectedShipping === opt"
                        :aria-label="`${group.name} ${opt.service}, ${formatIDR(opt.cost)}`"
                        @change="selectedShipping = opt"
                      />
                      <span
                        class="mt-0.5 grid h-5 w-5 flex-none place-items-center rounded-full border transition"
                        :class="selectedShipping === opt ? 'border-forest bg-forest text-ivory' : 'border-line bg-surface'"
                        aria-hidden="true"
                      >
                        <Check v-if="selectedShipping === opt" class="h-3 w-3" :stroke-width="2" />
                      </span>
                      <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                          <span class="font-semibold text-forest">{{ opt.service }}</span>
                          <span
                            v-for="badge in shippingBadges(opt)"
                            :key="badge"
                            class="border border-gold/35 bg-gold/[0.09] px-2 py-0.5 text-[0.62rem] font-semibold uppercase tracking-[0.08em] text-forest"
                          >
                            {{ badge }}
                          </span>
                        </span>
                        <span class="mt-1 block text-[0.72rem] leading-relaxed text-muted">{{ opt.description || 'Layanan pengiriman' }}</span>
                        <span class="mt-0.5 block text-[0.7rem] text-muted">{{ etdText(opt.etd) }}</span>
                      </span>
                      <span class="flex-none text-right text-[0.84rem] font-semibold text-forest">{{ formatIDR(opt.cost) }}</span>
                    </label>
                  </div>
                </section>
              </div>
            </template>
          </div>
          </div>

          <div class="mt-8 border border-dashed border-gold/40 bg-gold/[0.07] p-5">
            <div class="flex items-center gap-2.5">
              <Gift class="h-4 w-4 text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-xl text-forest">Kartu ucapan gratis</h2>
            </div>
            <label class="sr-only" for="giftmsg">Pesan kartu ucapan</label>
            <textarea
              id="giftmsg" v-model="form.giftMessage" rows="3" maxlength="180"
              class="field mt-4 bg-surface" placeholder="Contoh: Untuk Ibu, dari tanah suci. Doa kami sampai duluan."
            />
            <div class="mt-2 flex items-center justify-between">
              <label class="flex items-center gap-2 text-[0.78rem] text-forest">
                <input v-model="form.hideInvoice" type="checkbox" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
                Sembunyikan nota harga di dalam paket
              </label>
              <span class="text-[0.72rem] text-muted">{{ form.giftMessage.length }}/180</span>
            </div>
          </div>

          <div class="mt-6">
            <label class="field-label" for="note">Catatan pesanan (opsional)</label>
            <textarea
              id="note" v-model="form.note" rows="2" maxlength="500"
              class="field" placeholder="Contoh: tolong kirim sore hari, atau titip pesan ke kurir"
            />
          </div>
        </section>

        <!-- 2. Tinjau -->
        <section v-else class="mt-10">
          <p class="eyebrow">Langkah 2 dari 2</p>
          <h1 class="mt-4 text-[2rem] leading-[1.05] tracking-[-0.02em] sm:text-[2.2rem]">Tinjau pesanan</h1>
          <p class="mt-3 text-[0.85rem] text-muted">Periksa sekali lagi sebelum lanjut ke WhatsApp.</p>
          <dl class="mt-8 divide-y divide-line border-y border-line text-[0.87rem]">
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Pemesan</dt>
              <dd class="text-right text-forest">{{ form.name }}<span class="block text-[0.78rem] text-muted">{{ form.phone }}<template v-if="form.email"> · {{ form.email }}</template></span>
                <span v-if="errors.name || errors.phone" class="mt-1 block text-[0.75rem] text-danger">{{ errors.name || errors.phone }}</span>
              </dd>
            </div>
            <div class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Kirim ke</dt>
              <dd class="max-w-xs text-right text-forest">{{ fullAddress }}, {{ form.city }} {{ form.postal }}</dd>
            </div>
            <div v-if="selectedShipping" class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Kurir</dt>
              <dd class="max-w-xs text-right text-forest">
                {{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }}
                <span class="block text-[0.78rem] font-medium">{{ selectedShipping.service }}</span>
                <span class="mt-1 block text-[0.72rem] text-muted">{{ etdText(selectedShipping.etd) }}</span>
                <span v-if="shippingWeight" class="block text-[0.72rem] text-muted">Berat {{ weightText(shippingWeight) }}</span>
              </dd>
            </div>
            <div v-if="form.giftMessage" class="flex justify-between gap-6 py-4">
              <dt class="text-muted">Kartu ucapan</dt>
              <dd class="max-w-xs text-right font-display text-[1.05rem] italic text-forest">"{{ form.giftMessage }}"</dd>
            </div>
          </dl>
        </section>

        <!-- Modal input alamat -->
        <Teleport to="body">
          <Transition
            enter-active-class="transition duration-300 ease-calm"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-200 ease-calm"
            leave-to-class="opacity-0"
          >
            <div v-if="addressModalOpen" class="fixed inset-0 z-[200] bg-forest-deep/50 p-4 backdrop-blur-sm" @click.self="closeAddressModal">
              <div class="mx-auto flex max-h-[88dvh] w-full max-w-lg flex-col overflow-hidden border border-line bg-ivory shadow-soft">
                <header class="flex items-center justify-between gap-4 border-b border-line px-5 py-4 sm:px-6">
                  <div>
                    <h2 class="font-display text-lg text-forest">Alamat pengiriman</h2>
                    <p class="mt-0.5 text-[0.72rem] text-muted">Ongkir dihitung dari kota tujuan yang dipilih.</p>
                  </div>
                  <button type="button" class="grid h-9 w-9 flex-none place-items-center text-muted transition hover:text-forest" aria-label="Tutup" @click="closeAddressModal">
                    <X class="h-4 w-4" />
                  </button>
                </header>

                <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                  <div>
                    <label class="field-label" for="street">Jalan & nomor rumah</label>
                    <input id="street" v-model="form.street" class="field" placeholder="Contoh: Jl. Merdeka No. 10" :aria-invalid="!!errors.address" />
                    <p v-if="errors.address" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.address }}</p>
                  </div>
                  <div class="grid gap-5 sm:grid-cols-4">
                    <div>
                      <label class="field-label" for="rt">RT (opsional)</label>
                      <input id="rt" v-model="form.rt" inputmode="numeric" maxlength="3" class="field" placeholder="01" />
                    </div>
                    <div>
                      <label class="field-label" for="rw">RW (opsional)</label>
                      <input id="rw" v-model="form.rw" inputmode="numeric" maxlength="3" class="field" placeholder="02" />
                    </div>
                    <div class="sm:col-span-2">
                      <label class="field-label" for="landmark">Patokan (opsional)</label>
                      <input id="landmark" v-model="form.landmark" class="field" placeholder="Contoh: dekat Masjid Al-Ikhlas" />
                    </div>
                  </div>
                  <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                      <label class="field-label" for="city">Kelurahan / kecamatan / kota tujuan</label>
                      <template v-if="!manualCity">
                        <DestinationSearch
                          id="city"
                          v-model="form.destinationId"
                          :initial-label="form.city ? `${form.city}${form.province ? ', ' + form.province : ''}` : ''"
                          @select="onDestinationSelect"
                        />
                        <button type="button" class="mt-1.5 text-[0.72rem] text-muted underline transition hover:text-forest" @click="toggleManualCity">
                          Kotanya tidak ketemu? Isi manual saja
                        </button>
                      </template>
                      <template v-else>
                        <input id="city" v-model="form.city" class="field" placeholder="Contoh: Parepare" />
                        <button type="button" class="mt-1.5 text-[0.72rem] text-muted underline transition hover:text-forest" @click="toggleManualCity">
                          Pakai pencarian kota lagi
                        </button>
                      </template>
                      <p v-if="errors.destination" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.destination }}</p>
                    </div>
                    <div>
                      <label class="field-label" for="postal">Kode pos (opsional)</label>
                      <input id="postal" v-model="form.postal" inputmode="numeric" maxlength="5" class="field" placeholder="91114" />
                      <p class="mt-1.5 text-[0.68rem] leading-relaxed text-muted">Terisi otomatis setelah lokasi dipilih.</p>
                    </div>
                  </div>
                </div>

                <footer class="flex border-t border-line px-5 py-4 sm:px-6">
                  <AppButton size="lg" class="w-full" @click="saveAddress">Simpan alamat</AppButton>
                </footer>
              </div>
            </div>
          </Transition>
        </Teleport>

        <div class="mt-10 flex items-center gap-3">
          <AppButton v-if="step > 1" variant="quiet" size="lg" @click="back">Kembali</AppButton>
          <AppButton v-if="step < steps.length" size="lg" class="flex-1 sm:flex-none sm:min-w-[12rem]" :disabled="shippingLoading" @click="next">Lanjut</AppButton>
          <AppButton v-else size="lg" class="flex-1 sm:flex-none sm:min-w-[16rem]" :loading="submitting" :disabled="shippingLoading" @click="placeOrder">
            Buat Pesanan
          </AppButton>
        </div>
        <p class="mt-5 text-[0.72rem] text-muted">
          Metode bayar dikonfirmasi langsung di chat WhatsApp.
        </p>
        <p v-if="isGuest && step === steps.length" class="mt-4 flex items-start gap-2 border border-gold/40 bg-gold/[0.07] p-3 text-[0.78rem] leading-relaxed text-forest">
          <LockKeyhole class="mt-0.5 h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" />
          Anda akan diminta masuk akun dulu saat membuat pesanan. Keranjang dan isian ini tetap tersimpan.
        </p>

        <button
          type="button"
          class="mt-6 flex w-full items-center justify-between gap-4 border border-line bg-surface p-4 text-left lg:hidden"
          @click="summaryOpen = true"
        >
          <span>
            <span class="block text-[0.72rem] uppercase tracking-[0.14em] text-muted">Ringkasan pesanan</span>
            <span class="mt-0.5 block font-display text-xl text-forest">{{ formatIDR(shippingCostDisplay !== null ? grandTotal : cart.subtotal.value) }}</span>
          </span>
          <span class="flex flex-none items-center gap-2 text-[0.78rem] text-forest">
            {{ cart.count.value }} pcs <ChevronUp class="h-4 w-4 text-gold" :stroke-width="1.5" />
          </span>
        </button>
      </div>

      <!-- Ringkasan: kartu panel elevated, konsisten dengan drawer keranjang (layar besar) -->
      <aside class="hidden border-t border-line px-5 py-10 sm:px-10 lg:sticky lg:top-0 lg:h-[100dvh] lg:overflow-y-auto lg:border-l lg:border-t-0 lg:py-14 lg:block">
        <div class="rounded-[0.75rem] border border-line bg-surface p-6 shadow-soft sm:p-7">
          <div class="flex items-start justify-between gap-4">
            <div>
              <p class="eyebrow">Checkout</p>
              <h2 class="mt-3 font-display text-2xl tracking-[-0.02em]">Ringkasan pesanan</h2>
            </div>
            <span class="flex-none rounded-full border border-line bg-ivory px-3 py-1 text-[0.72rem] font-semibold text-forest">{{ cart.count.value }} pcs</span>
          </div>

          <ul class="mt-7 space-y-4">
            <li v-for="item in cart.items.value" :key="item.id" class="flex gap-4">
              <div class="relative flex-none">
                <span class="arch block h-20 w-16 overflow-hidden border border-line">
                  <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
                  <ProductArt v-else :art="item.art" :tone="item.id" />
                </span>
                <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-forest text-[0.68rem] text-ivory">{{ item.qty }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate font-display text-[1.02rem] leading-tight text-forest">{{ item.name }}</p>
                <p class="mt-1 text-[0.75rem] text-muted">{{ formatIDR(item.price) }} × {{ item.qty }}</p>
              </div>
              <p class="flex-none text-[0.88rem] font-semibold text-forest">{{ formatIDR(item.lineTotal) }}</p>
            </li>
          </ul>

          <dl class="mt-8 space-y-3.5 border-t border-line pt-6 text-[0.88rem]">
            <div class="flex items-start justify-between gap-4">
              <dt class="text-muted">Subtotal <span class="text-[0.72rem]">({{ cart.count.value }} pcs)</span></dt>
              <dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4">
              <dt class="text-muted">Ongkos kirim</dt>
              <dd class="text-right">
                <template v-if="hasFreeShipping">
                  <span class="inline-block rounded-full border border-olive/40 bg-olive/10 px-2.5 py-0.5 text-[0.72rem] font-semibold text-olive">Gratis</span>
                </template>
                <template v-else-if="selectedShipping">
                  <span class="font-semibold text-forest">{{ formatIDR(selectedShipping.cost) }}</span>
                  <span class="mt-1 block text-[0.72rem] text-muted">{{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }}</span>
                  <span class="block text-[0.68rem] text-muted/70">{{ selectedShipping.service }}. {{ etdText(selectedShipping.etd) }}</span>
                  <span v-if="shippingWeight" class="block text-[0.68rem] text-muted/70">Berat {{ weightText(shippingWeight) }}</span>
                </template>
                <template v-else-if="shippingLoading">
                  <span class="mt-0.5 block h-4 w-24 rounded bg-line motion-safe:animate-pulse" />
                </template>
                <template v-else-if="manualCity || form.destinationId">
                  <span class="inline-block rounded-full border border-gold/40 bg-gold/[0.09] px-2.5 py-0.5 text-[0.72rem] font-medium text-forest">Dikonfirmasi via WhatsApp</span>
                </template>
                <template v-else><span class="text-muted">Isi kota tujuan dulu</span></template>
              </dd>
            </div>
            <div v-if="cart.savings.value" class="flex items-center justify-between gap-4">
              <dt class="text-muted">Anda hemat</dt>
              <dd class="font-semibold text-olive">{{ formatIDR(cart.savings.value) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-4">
              <dt class="flex items-center gap-1.5 text-muted">
                <Gift class="h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" /> Kartu ucapan
              </dt>
              <dd class="text-olive">Gratis</dd>
            </div>
          </dl>

          <div class="mt-6 rounded-[0.6rem] bg-forest-deep px-5 py-4 text-ivory">
            <div class="flex items-end justify-between gap-4">
              <span class="text-[0.82rem] text-ivory/70">
                <template v-if="shippingCostDisplay !== null">Total</template>
                <template v-else>Estimasi total <span class="block text-[0.68rem]">(belum termasuk ongkir)</span></template>
              </span>
              <span class="font-display text-[1.9rem] leading-none tracking-[-0.02em]">{{ formatIDR(shippingCostDisplay !== null ? grandTotal : cart.subtotal.value) }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- Ringkasan pesanan: bottom sheet untuk layar kecil -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-300 ease-calm" enter-from-class="opacity-0" leave-active-class="transition duration-[250ms] ease-calm" leave-to-class="opacity-0">
          <div v-if="summaryOpen" class="fixed inset-0 z-[160] bg-forest-deep/40 backdrop-blur-[2px]" @click="summaryOpen = false" />
        </Transition>
        <Transition enter-active-class="transition duration-[420ms] ease-calm" enter-from-class="translate-y-full" leave-active-class="transition duration-300 ease-calm" leave-to-class="translate-y-full">
          <div
            v-if="summaryOpen"
            class="fixed inset-x-0 bottom-0 z-[161] flex max-h-[85dvh] flex-col overflow-hidden rounded-t-[1rem] border-t border-forest-soft/20 bg-ivory shadow-lift sm:mx-auto sm:max-w-md"
            role="dialog" aria-modal="true" aria-label="Ringkasan pesanan"
          >
            <header class="flex items-center justify-between border-b border-forest-soft/20 bg-forest-deep px-6 py-5">
              <h2 class="font-display text-2xl text-ivory">
                Ringkasan pesanan
                <span class="ml-1 align-middle text-[0.8rem] text-ivory/50">({{ cart.count.value }} pcs)</span>
              </h2>
              <button class="grid h-9 w-9 place-items-center text-ivory/70 transition hover:text-ivory" aria-label="Tutup ringkasan" @click="summaryOpen = false">
                <X class="h-[18px] w-[18px]" :stroke-width="1.5" />
              </button>
            </header>

            <div class="flex-1 overflow-y-auto px-5 py-5">
              <ul class="space-y-3">
                <li v-for="item in cart.items.value" :key="item.id" class="flex gap-4 rounded-[0.75rem] border border-line bg-surface p-4">
                  <div class="relative flex-none">
                    <span class="arch block h-20 w-16 overflow-hidden border border-line">
                      <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
                      <ProductArt v-else :art="item.art" :tone="item.id" />
                    </span>
                    <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-forest text-[0.68rem] text-ivory">{{ item.qty }}</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate font-display text-[1.02rem] leading-tight text-forest">{{ item.name }}</p>
                    <p class="mt-1 text-[0.75rem] text-muted">{{ formatIDR(item.price) }} × {{ item.qty }}</p>
                  </div>
                  <p class="flex-none text-[0.88rem] font-semibold text-forest">{{ formatIDR(item.lineTotal) }}</p>
                </li>
              </ul>

              <dl class="mt-6 space-y-3.5 border-t border-line pt-5 text-[0.88rem]">
                <div class="flex items-start justify-between gap-4">
                  <dt class="text-muted">Subtotal <span class="text-[0.72rem]">({{ cart.count.value }} pcs)</span></dt>
                  <dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                  <dt class="text-muted">Ongkos kirim</dt>
                  <dd class="text-right">
                    <template v-if="hasFreeShipping">
                      <span class="inline-block rounded-full border border-olive/40 bg-olive/10 px-2.5 py-0.5 text-[0.72rem] font-semibold text-olive">Gratis</span>
                    </template>
                    <template v-else-if="selectedShipping">
                      <span class="font-semibold text-forest">{{ formatIDR(selectedShipping.cost) }}</span>
                      <span class="mt-1 block text-[0.72rem] text-muted">{{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }}</span>
                      <span class="block text-[0.68rem] text-muted/70">{{ selectedShipping.service }}. {{ etdText(selectedShipping.etd) }}</span>
                      <span v-if="shippingWeight" class="block text-[0.68rem] text-muted/70">Berat {{ weightText(shippingWeight) }}</span>
                    </template>
                    <template v-else-if="shippingLoading">
                      <span class="mt-0.5 block h-4 w-24 rounded bg-line motion-safe:animate-pulse" />
                    </template>
                    <template v-else-if="manualCity || form.destinationId">
                      <span class="inline-block rounded-full border border-gold/40 bg-gold/[0.09] px-2.5 py-0.5 text-[0.72rem] font-medium text-forest">Dikonfirmasi via WhatsApp</span>
                    </template>
                    <template v-else><span class="text-muted">Isi kota tujuan dulu</span></template>
                  </dd>
                </div>
                <div v-if="cart.savings.value" class="flex items-center justify-between gap-4">
                  <dt class="text-muted">Anda hemat</dt>
                  <dd class="font-semibold text-olive">{{ formatIDR(cart.savings.value) }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                  <dt class="flex items-center gap-1.5 text-muted">
                    <Gift class="h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" /> Kartu ucapan
                  </dt>
                  <dd class="text-olive">Gratis</dd>
                </div>
              </dl>

              <div class="mt-5 flex items-end justify-between gap-4 rounded-[0.6rem] bg-forest-deep px-5 py-4 text-ivory">
                <span class="text-[0.82rem] text-ivory/70">
                  <template v-if="shippingCostDisplay !== null">Total</template>
                  <template v-else>Estimasi total <span class="block text-[0.68rem]">(belum termasuk ongkir)</span></template>
                </span>
                <span class="font-display text-[1.9rem] leading-none tracking-[-0.02em]">{{ formatIDR(shippingCostDisplay !== null ? grandTotal : cart.subtotal.value) }}</span>
              </div>
            </div>

            <div class="border-t border-line bg-surface px-6 py-4">
              <button class="w-full text-[0.78rem] text-muted transition hover:text-forest" @click="summaryOpen = false">
                Tutup ringkasan
              </button>
              <div class="h-safe-bottom" />
            </div>
          </div>
        </Transition>
      </Teleport>
    </div>

    <div v-else class="shell py-24">
      <EmptyState title="Belum ada yang bisa dipesan" body="Keranjang Anda kosong. Pilih dulu hadiahnya, lalu kembali ke sini.">
        <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
      </EmptyState>
    </div>
  </div>
</template>
