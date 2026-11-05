<script>
import BareLayout from '@/layouts/BareLayout.vue'
export default { layout: BareLayout }
</script>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  AlertCircle, ArrowLeft, Banknote, Check, ChevronRight, ChevronUp, Copy, CreditCard,
  Gift, Landmark, MapPin, MessageCircle, QrCode, ShieldCheck, TicketPercent, Truck, User, X,
} from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import BrandLogo from '@/components/storefront/BrandLogo.vue'
import DestinationSearch from '@/components/shop/DestinationSearch.vue'
import MapPinPicker from '@/components/shop/MapPinPicker.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { formatIDR } from '@/composables/useFormat'
import { useCart } from '@/composables/useCart'
import { useStore } from '@/composables/useStore'
import { useToast } from '@/composables/useToast'
import { track } from '@/composables/useAnalytics'
import { statusMeta } from '@/data/admin'

const page = usePage()
const store = computed(() => page.props.store)
const paymentMethods = computed(() => page.props.paymentMethods ?? [])

const cart = useCart()
const { whatsappHref } = useStore()
const { push } = useToast()

// Bentuk item untuk GA4/Meta. Item pesanan lama hanya punya SKU, bukan id.
const analyticsItems = (items) => (items ?? []).map((item) => ({
  item_id: item.id ?? item.sku ?? item.name,
  item_name: item.name,
  item_category: item.category,
  price: item.price,
  quantity: item.qty ?? item.quantity ?? 1,
}))

const helpHref = computed(() =>
  whatsappHref('Halo ArafahGift, saya butuh bantuan untuk menyelesaikan pesanan saya.'),
)

// Laris/instan bukan klaim: label ini cuma menjelaskan cara bayarnya.
const paymentIcons = { qris: QrCode, transfer: Landmark, cod: Banknote, card: CreditCard }
const paymentIcon = (id) => paymentIcons[id] ?? CreditCard

const copyValue = async (value, label) => {
  if (!value) return
  try {
    await navigator.clipboard.writeText(value)
    push(`${label} disalin`, { tone: 'success' })
  } catch {
    push('Tidak bisa menyalin otomatis. Salin manual dari layar ya.', { tone: 'danger' })
  }
}

const pickupEnabled = computed(() => store.value.pickupEnabled !== false)
const storeClosed = computed(() => (store.value.storeStatus ?? 'open') !== 'open')
const minOrderAmount = computed(() => Number(store.value.minOrderAmount ?? 0))
const belowMinimum = computed(() => minOrderAmount.value > 0 && cart.subtotal.value < minOrderAmount.value)

// Tamu boleh checkout tanpa login; yang sudah login datanya terisi otomatis dari akun.
const user = computed(() => page.props.auth?.user)

const steps = [
  { id: 1, label: 'Alamat' },
  { id: 2, label: 'Tinjau' },
]
const step = ref(1)
const initialMode = new URLSearchParams(window.location.search).get('mode')
const fulfillment = ref(initialMode === 'pickup' && pickupEnabled.value ? 'pickup' : 'delivery')
const isPickup = computed(() => fulfillment.value === 'pickup')
const paymentMethod = ref(paymentMethods.value[0]?.id ?? '')
const voucherCode = ref('')
const voucher = ref(null)
const voucherError = ref(null)
const voucherLoading = ref(false)
const saveProfile = ref(false)
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
const shippingChoiceOpen = ref(false)

watch(addressModalOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

const saveAddress = () => {
  delete errors.address
  delete errors.destination
  if (isPickup.value) {
    addressModalOpen.value = false
    return
  }
  if (form.street.trim().length < 8) errors.address = 'Tulis nama jalan dan nomor rumah.'
  if (manualCity.value) {
    if (!form.city.trim()) errors.destination = 'Isi kota atau kabupaten.'
  } else if (!form.destinationId) {
    errors.destination = 'Cari dan pilih kota/kecamatan tujuan.'
  }
  if (Object.keys(errors).length) return
  addressModalOpen.value = false
  shippingChoiceOpen.value = true
}

const summaryOpen = ref(false)
watch(summaryOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})
watch(shippingChoiceOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

const backToCart = () => {
  const previousUrl = document.referrer
  if (window.history.length > 1 && previousUrl.startsWith(window.location.origin)) {
    window.history.back()
    return
  }
  window.location.href = '/koleksi'
}

const form = reactive({
  name: '', email: '', phone: '',
  street: '', rt: '', rw: '', landmark: '', subdistrict: '', district: '', city: '', province: '', postal: '', destinationId: null,
  giftMessage: '', note: '', hideInvoice: true, pin: null,
})

// ponytail: koordinat disimpan sebagai tautan Google Maps di catatan pesanan (tanpa kolom/migrasi baru).
const pinUrl = computed(() => form.pin ? `https://maps.google.com/?q=${form.pin.lat},${form.pin.lng}` : null)

// Digabung jadi satu baris alamat siap-kirim (WhatsApp, ringkasan, payload) dari kolom terpisah.
// dipecah biar user lebih terpandu isi detailnya, tapi sisi server/WA tetap terima satu string alamat.
const fullAddress = computed(() => {
  const parts = [form.street.trim()]
  const neighborhood = [
    form.rt.trim() ? `RT ${form.rt.trim()}` : '',
    form.rw.trim() ? `RW ${form.rw.trim()}` : '',
  ].filter(Boolean).join(' / ')
  if (neighborhood) parts.push(neighborhood)
  if (form.subdistrict.trim()) parts.push(`Kel. ${form.subdistrict.trim()}`)
  if (form.district.trim()) parts.push(`Kec. ${form.district.trim()}`)
  const base = parts.filter(Boolean).join(', ')
  return form.landmark.trim() ? `${base} (Patokan: ${form.landmark.trim()})` : base
})

// ponytail: manual fallback exists only so a total RajaOngkir outage can't block checkout entirely.
const manualCity = ref(false)
const toggleManualCity = () => {
  manualCity.value = !manualCity.value
  form.destinationId = null
  form.subdistrict = ''
  form.district = ''
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
    if (form.name.trim().length < 3) errors.name = 'Tulis nama lengkap penerima pesanan.'
    if (form.phone.replace(/\D/g, '').length < 9) errors.phone = 'Nomor WhatsApp minimal 9 angka.'
    if (!/^\S+@\S+\.\S+$/.test(form.email.trim())) errors.email = 'Isi email yang valid untuk menerima konfirmasi pesanan.'
    if (!isPickup.value) {
      if (form.street.trim().length < 8) errors.address = 'Tulis nama jalan dan nomor rumah.'
      if (manualCity.value) {
        if (!form.city.trim()) errors.destination = 'Isi kota atau kabupaten.'
      } else if (!form.destinationId) {
        errors.destination = 'Cari dan pilih kota/kecamatan tujuan.'
      } else if (!shippingUnavailable.value && !hasFreeShipping.value && !selectedShipping.value) {
        errors.destination = 'Tunggu pilihan kurir termuat, atau pilih salah satu.'
      }
    }
  }
  return Object.keys(errors).length === 0
}

const next = () => {
  const ok = validate(step.value)
  if (ok) {
    step.value = Math.min(steps.length, step.value + 1)
    nextTick(() => window.scrollTo({ top: 0, behavior: 'smooth' }))
    return
  }
  // Alamat belum diisi: buka modal isi alamat langsung supaya validasinya terlihat.
  if (step.value === 1 && !isPickup.value && !form.street.trim()) openAddressModal()
}
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
const deliveryMethod = ref(null)

const cargoMarkers = ['cargo', 'trucking', 'jtr', 'gokil']
const isCargoService = (option) => {
  const searchable = `${option.service ?? ''} ${option.description ?? ''}`.toLowerCase()
  return cargoMarkers.some((marker) => searchable.includes(marker))
}

const eligibleShippingOptions = computed(() => shippingOptions.value
  .filter((option) => !(shippingWeight.value < 10000 && isCargoService(option)))
  .slice()
  .sort((a, b) => Number(a.cost) - Number(b.cost)))

const isMakassar = computed(() => /\bmakassar\b/i.test(form.city.trim()))
const deliveryCityReady = computed(() =>
  !isPickup.value && form.city.trim() && (manualCity.value || form.destinationId),
)
const arafaCourierOption = computed(() => ({
  courier: 'arafagift',
  courier_name: 'Kurir ArafahGift',
  service: 'Antar oleh tim ArafahGift',
  description: isMakassar.value
    ? `Gratis untuk Kota Makassar. Pengantaran ${store.value.arafagiftDeliveryStart ?? '13:00'}-${store.value.arafagiftDeliveryEnd ?? '16:00'} WITA, dikonfirmasi admin.`
    : 'Tersedia di luar Makassar. Biaya dan jadwal dikonfirmasi via WhatsApp.',
  etd: '',
  cost: isMakassar.value ? 0 : null,
  manual: true,
}))

const chooseDeliveryMethod = (method) => {
  deliveryMethod.value = method
  shippingChoiceOpen.value = false
  if (method === 'arafagift') {
    selectedShipping.value = arafaCourierOption.value
    return
  }
  selectedShipping.value = eligibleShippingOptions.value[0] ?? null
}

const changeDeliveryMethod = () => {
  shippingChoiceOpen.value = true
}

const expandedShippingGroups = ref(new Set(['arafagift']))
const isShippingGroupOpen = (code) => expandedShippingGroups.value.has(code)
const toggleShippingGroup = (code) => {
  const next = new Set(expandedShippingGroups.value)
  if (next.has(code)) next.delete(code)
  else next.add(code)
  expandedShippingGroups.value = next
}

const shippingGroups = computed(() => {
  const groups = new Map()
  const options = !isPickup.value
    ? [
      ...(deliveryMethod.value === 'arafagift' ? [arafaCourierOption.value] : []),
      ...(deliveryMethod.value === 'expedition' ? eligibleShippingOptions.value : []),
    ]
    : eligibleShippingOptions.value
  options.forEach((option) => {
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
    form.subdistrict = ''
    form.district = ''
    form.city = ''
    form.province = ''
    form.postal = ''
    shippingOptions.value = []
    selectedShipping.value = null
    shippingUnavailable.value = false
    shippingError.value = null
    return
  }
  form.subdistrict = destination.subdistrict || ''
  form.district = destination.district || ''
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

// Isi otomatis alamat dari hasil reverse-geocode titik peta. Yang sudah diketik pelanggan di
// jalan/RT/RW/patokan tidak ditimpa; kota tujuan dicari lewat kode pos (paling presisi) lalu kelurahan/kecamatan.
const destinationRef = ref(null)
const searchDestination = async (term) => {
  const res = await fetch(`/api/shipping/destinations?search=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' } })
  const payload = res.ok ? await res.json() : null
  return payload?.success && Array.isArray(payload.data) ? payload.data : []
}
const applyLocation = async (a) => {
  const displayStreet = String(a.display_name ?? '').split(',')[0].trim()
  const road = a.road || a.pedestrian || a.residential || a.footway || a.path || displayStreet
  const building = a.house_number || a.building || ''
  const street = [road, building && (a.house_number ? `No. ${building}` : building)].filter(Boolean).join(' ')
  if (!form.street.trim() && street) form.street = street
  if (!form.landmark.trim() && a.amenity) form.landmark = `dekat ${a.amenity}`
  const area = a.village || a.suburb || a.neighbourhood || a.quarter || ''
  const district = a.city_district || a.district || a.county || ''
  const city = a.city || a.town || a.municipality || a.regency || ''
  if (manualCity.value) {
    if (!form.subdistrict.trim()) form.subdistrict = area
    if (!form.district.trim()) form.district = district
    if (!form.city.trim()) form.city = city || district
    if (!form.postal && a.postcode) form.postal = a.postcode
    return
  }
  for (const term of [a.postcode, `${area} ${district}`, district || city]) {
    if (!term || term.trim().length < 3) continue
    try {
      const [first] = await searchDestination(term.trim())
      if (first) return destinationRef.value?.choose(first)
    } catch { /* abaikan, pelanggan bisa cari manual */ }
  }
}

const etdText = (etd) => {
  const value = String(etd ?? '').trim()
  if (!value || value === '-') return 'Estimasi belum tersedia'
  return `Estimasi ${value.replace(/\bdays?\b/gi, 'hari')}`
}
const weightText = (grams) => `${(grams / 1000).toLocaleString('id-ID', { maximumFractionDigits: 2 })} kg`

const shippingCostDisplay = computed(() => {
  if (isPickup.value) return 0
  if (selectedShipping.value?.manual) return isMakassar.value ? 0 : null
  if (hasFreeShipping.value) return 0
  return selectedShipping.value ? selectedShipping.value.cost : null
})
const discountAmount = computed(() => voucher.value?.discount ?? 0)
const grandTotal = computed(() => Math.max(0, cart.subtotal.value - discountAmount.value + (shippingCostDisplay.value ?? 0)))

const selectedPayment = computed(() => paymentMethods.value.find((method) => method.id === paymentMethod.value) ?? null)

// Instruksi bayar manual: rekening toko untuk transfer, gambar QRIS kalau ada, atau COD.
const paymentInstructions = computed(() => {
  const code = selectedPayment.value?.id

  if (code === 'transfer') {
    return [
      store.value.bankName && `Bank ${store.value.bankName}`,
      store.value.bankAccountNumber && `No. rekening ${store.value.bankAccountNumber}`,
      store.value.bankAccountName && `a.n. ${store.value.bankAccountName}`,
      store.value.paymentInstructions,
    ].filter(Boolean).join('\n')
  }

  if (code === 'qris') {
    return store.value.paymentInstructions || 'Scan QRIS yang dikirim admin melalui WhatsApp.'
  }

  if (code === 'cod') {
    return selectedPayment.value?.instructions || 'Bayar tunai saat pesanan diterima atau diambil di toko.'
  }

  return selectedPayment.value?.instructions || store.value.paymentInstructions || 'Instruksi pembayaran dikirim melalui WhatsApp.'
})

// Transfer menampilkan rincian rekening terstruktur (dengan tombol salin),
// jadi panel instruksi cukup memuat catatan tambahan dari pengaturan toko.
const paymentExtra = computed(() => {
  if (selectedPayment.value?.id === 'transfer') return store.value.paymentInstructions || ''
  return paymentInstructions.value
})

const editStep = () => {
  step.value = 1
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const applyVoucher = async () => {
  const code = voucherCode.value.trim()
  if (!code) return
  voucherLoading.value = true
  voucherError.value = null
  try {
    const res = await fetch('/checkout/voucher', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        code,
        items: cart.items.value.map((i) => ({ id: i.id, qty: i.qty })),
      }),
    })
    const data = await res.json().catch(() => null)
    if (!res.ok) {
      voucherError.value = data?.errors?.voucher?.[0] || data?.message || 'Voucher tidak bisa dipakai.'
      voucher.value = null
      return
    }
    voucher.value = data
    voucherCode.value = data.code ?? code
  } catch {
    voucherError.value = 'Gagal memeriksa voucher. Coba lagi.'
  } finally {
    voucherLoading.value = false
  }
}

const removeVoucher = () => {
  voucher.value = null
  voucherCode.value = ''
  voucherError.value = null
}

// Simpan data checkout untuk pesanan berikutnya (opt-in, hanya di perangkat pelanggan).
const PROFILE_KEY = 'arafahgift.checkout-profile'
const loadProfile = () => {
  try {
    const raw = localStorage.getItem(PROFILE_KEY)
    if (!raw) return
    const data = JSON.parse(raw)
    form.name = data.name ?? ''
    form.phone = data.phone ?? ''
    form.email = data.email ?? ''
    form.street = data.street ?? ''
    form.rt = data.rt ?? ''
    form.rw = data.rw ?? ''
    form.landmark = data.landmark ?? ''
    saveProfile.value = true
  } catch { /* abaikan */ }
}
const persistProfile = () => {
  try {
    if (!saveProfile.value) {
      localStorage.removeItem(PROFILE_KEY)
      return
    }
    localStorage.setItem(PROFILE_KEY, JSON.stringify({
      name: form.name, phone: form.phone, email: form.email,
      street: form.street, rt: form.rt, rw: form.rw, landmark: form.landmark,
    }))
  } catch { /* mode privat: abaikan */ }
}

const buildWaMessage = (order) => {
  const subtotal = order.items.reduce((sum, i) => sum + i.price * i.qty, 0)
  const pickupOrder = order.fulfillment === 'pickup'
  const shippingLine = pickupOrder
    ? 'Pengambilan: Ambil di tempat (tanpa ongkir)'
    : order.shipping.courier !== 'Belum ditentukan'
      ? `Ongkir: ${order.shipping.courier.toUpperCase()} ${order.shipping.method}, ${formatIDR(order.shipping.cost)}${order.shipping.etd ? ` (${etdText(order.shipping.etd)})` : ''}`
      : `Ongkir: ${hasFreeShipping.value ? 'Gratis' : 'akan dikonfirmasi ya'}`
  const lines = [
    `Halo ArafahGift, saya mau pesan:`,
    '',
    ...order.items.map((i) => `• ${i.name} × ${i.qty}: ${formatIDR(i.price * i.qty)}`),
    '',
    `Subtotal: ${formatIDR(subtotal)}`,
    order.discount > 0 ? `Diskon${order.voucherCode ? ` (${order.voucherCode})` : ''}: -${formatIDR(order.discount)}` : null,
    shippingLine,
    `Total: ${formatIDR(order.total ?? grandTotal.value)}`,
    '',
    `Nomor pesanan: ${order.id}`,
    `Metode pembayaran: ${order.payment}`,
    `Instruksi bayar: ${paymentInstructions.value.replace(/\n/g, ' | ')}`,
    `Nama: ${form.name}`,
    `WhatsApp: ${form.phone}`,
    form.email ? `Email: ${form.email}` : null,
    pickupOrder ? 'Ambil di: ' + (store.value.address || 'toko ArafahGift') : `Alamat: ${order.address || `${fullAddress.value}, ${form.city}${form.postal ? ` ${form.postal}` : ''}${form.province ? `, ${form.province}` : ''}`}`,
    !pickupOrder && pinUrl.value ? `Titik peta: ${pinUrl.value}` : null,
    form.giftMessage ? `Kartu ucapan: "${form.giftMessage}"` : null,
    form.hideInvoice ? `Tolong sembunyikan nota harga di dalam paket.` : null,
    order.trackingUrl ? `Lacak pesanan: ${order.trackingUrl}` : null,
  ]
  return lines.filter((l) => l !== null).join('\n')
}

const submitting = ref(false)
const placedOrder = ref(null)
const confirmationSeconds = ref(15 * 60)
let confirmationTimer = null

const confirmationTime = computed(() => {
  const minutes = Math.floor(confirmationSeconds.value / 60).toString().padStart(2, '0')
  const seconds = (confirmationSeconds.value % 60).toString().padStart(2, '0')
  return `${minutes}:${seconds}`
})

const startConfirmationTimer = (order) => {
  if (confirmationTimer) clearInterval(confirmationTimer)
  const createdAt = Date.parse(order?.date ?? '')
  const deadline = Number.isNaN(createdAt) ? Date.now() + 15 * 60 * 1000 : createdAt + 15 * 60 * 1000
  const update = () => {
    confirmationSeconds.value = Math.max(0, Math.ceil((deadline - Date.now()) / 1000))
    if (!confirmationSeconds.value && confirmationTimer) {
      clearInterval(confirmationTimer)
      confirmationTimer = null
    }
  }
  update()
  confirmationTimer = window.setInterval(update, 1000)
}

const placeOrder = async () => {
  clearServerFeedback()
  if (!validate(1)) return
  delete errors.payment
  if (!paymentMethod.value) errors.payment = 'Pilih metode pembayaran.'
  if (storeClosed.value) {
    serverMessage.value = store.value.closedMessage || 'Toko sedang tutup. Silakan coba lagi saat jam operasional.'
    return
  }
  if (belowMinimum.value) {
    serverMessage.value = `Minimum belanja ${formatIDR(minOrderAmount.value)}. Tambah ${formatIDR(minOrderAmount.value - cart.subtotal.value)} lagi.`
    return
  }
  if (Object.keys(errors).length) return
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
        fulfillment_type: fulfillment.value,
        payment_method: paymentMethod.value,
        voucher_code: voucher.value?.code || null,
        name: form.name,
        phone: form.phone,
        email: form.email || null,
        address: isPickup.value ? null : fullAddress.value,
        city: isPickup.value ? null : form.city,
        province: isPickup.value ? null : (form.province || null),
        postal: isPickup.value ? null : (form.postal || null),
        destination_id: isPickup.value ? null : (form.destinationId || null),
        courier: isPickup.value ? null : (selectedShipping.value?.courier || null),
        service: isPickup.value ? null : (selectedShipping.value?.service || null),
        giftMessage: form.giftMessage || null,
        note: [form.note, !isPickup.value && pinUrl.value && `Titik peta: ${pinUrl.value}`].filter(Boolean).join('\n') || null,
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
          } else if (key === 'payment_method') {
            errors.payment = message
          } else if (key === 'checkout' || key === 'fulfillment_type') {
            serverMessage.value = message
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
    track('purchase', {
      transaction_id: placedOrder.value.id,
      currency: 'IDR',
      value: placedOrder.value.total,
      shipping: placedOrder.value.shipping?.cost ?? 0,
      items: analyticsItems(placedOrder.value.items),
    })
    startConfirmationTimer(placedOrder.value)
    persistProfile()
    cart.clear()
    clearServerFeedback()
  } catch {
    serverMessage.value = 'Terjadi kesalahan jaringan. Coba lagi.'
  } finally {
    submitting.value = false
  }
}

const confirmViaWhatsapp = () => {
  window.open(whatsappHref(buildWaMessage(placedOrder.value)), '_blank', 'noopener')
}

onUnmounted(() => {
  if (confirmationTimer) clearInterval(confirmationTimer)
})

onMounted(() => {
  loadProfile()

  if (cart.items.value.length) {
    track('begin_checkout', {
      currency: 'IDR',
      value: cart.subtotal.value,
      items: analyticsItems(cart.items.value),
    })
  }

  if (!paymentMethod.value) paymentMethod.value = paymentMethods.value[0]?.id ?? ''
  if (user.value) {
    if (!form.name) form.name = user.value.name ?? ''
    if (!form.phone) form.phone = user.value.phone ?? ''
    if (!form.email) form.email = user.value.email ?? ''
  }
  if (!isPickup.value && !manualCity.value && form.destinationId && !hasFreeShipping.value) fetchShipping()
  else shippingUnavailable.value = manualCity.value
})

// Pindah dari ambil di toko ke antar: kalau alamat sudah pernah diisi, hitung ongkirnya sekarang.
watch(fulfillment, (mode) => {
  if (mode === 'delivery' && !manualCity.value && form.destinationId && !hasFreeShipping.value) {
    fetchShipping()
  }
})
</script>

<template>
  <Head :title="page.props.seoHead.title" />
  <div class="min-h-[100dvh] bg-ivory">
    <div v-if="placedOrder" class="mx-auto max-w-[34rem] px-5 py-10 sm:px-8 sm:py-14">
      <div class="flex items-center justify-between gap-4">
        <BrandLogo size="sm" />
        <a :href="helpHref" target="_blank" rel="noopener" class="flex items-center gap-1.5 text-[0.76rem] text-muted transition hover:text-forest">
          <MessageCircle class="h-3.5 w-3.5" :stroke-width="1.5" /> Butuh bantuan?
        </a>
      </div>

      <div class="mt-8 overflow-hidden rounded-[0.75rem] border border-line bg-surface shadow-soft">
        <div class="relative overflow-hidden bg-forest-deep px-6 pb-7 pt-8 text-center text-ivory">
          <span class="pointer-events-none absolute -right-12 -top-12 h-36 w-36 rounded-full border border-gold/20" aria-hidden="true" />
          <span class="pointer-events-none absolute -bottom-16 -left-12 h-40 w-40 rounded-full border border-gold/10" aria-hidden="true" />
          <span class="mx-auto grid h-14 w-14 place-items-center rounded-full border border-gold/50 bg-gold/15">
            <Check class="h-7 w-7 text-gold" :stroke-width="2" />
          </span>
          <h1 class="mt-5 text-[1.7rem] leading-tight text-ivory sm:text-[1.9rem]">Pesanan siap dikonfirmasi</h1>
          <p class="mx-auto mt-2 max-w-sm text-[0.82rem] leading-relaxed text-ivory/70">
            Terima kasih, {{ placedOrder.customer }}. Pesanan Anda sudah tercatat. Kirim konfirmasi agar admin dapat menyiapkan langkah berikutnya.
          </p>
        </div>

        <!-- Nomor pesanan + salin -->
        <div class="flex items-center justify-between gap-4 border-b border-line bg-ivory/60 px-6 py-5">
          <div>
            <p class="text-[0.64rem] font-semibold uppercase tracking-[0.16em] text-muted">Nomor pesanan</p>
            <p class="mt-1 font-display text-[1.35rem] tracking-wide text-forest">{{ placedOrder.id }}</p>
          </div>
          <button
            type="button"
            class="inline-flex flex-none items-center gap-1.5 rounded-full border border-forest/20 bg-surface px-3 py-1.5 text-[0.72rem] font-semibold text-forest transition hover:border-forest active:translate-y-px"
            @click="copyValue(placedOrder.id, 'Nomor pesanan')"
          >
            <Copy class="h-3.5 w-3.5" :stroke-width="1.6" /> Salin
          </button>
        </div>

        <ol class="px-6 py-2">
          <li class="flex gap-3 py-4">
            <span class="grid h-6 w-6 flex-none place-items-center rounded-full bg-forest text-ivory">
              <Check class="h-3.5 w-3.5" :stroke-width="2.5" />
            </span>
            <span>
              <span class="block text-[0.85rem] font-semibold text-forest">Pesanan tercatat</span>
              <span class="mt-0.5 block text-[0.75rem] text-muted">Status {{ statusMeta(placedOrder.status).label }} · {{ placedOrder.payment }}</span>
            </span>
          </li>
          <li class="flex gap-3 border-t border-line py-4">
            <span class="grid h-6 w-6 flex-none place-items-center rounded-full border-2 border-gold bg-gold/15 text-[0.7rem] font-semibold text-forest">2</span>
            <span>
              <span class="block text-[0.85rem] font-semibold text-forest">Konfirmasi via WhatsApp</span>
              <span class="mt-0.5 block text-[0.75rem] leading-relaxed text-muted">Pesanan kami proses setelah Anda mengirim pesan konfirmasi.</span>
            </span>
          </li>
          <li class="flex gap-3 border-t border-line py-4">
            <span class="grid h-6 w-6 flex-none place-items-center rounded-full border border-line text-[0.7rem] font-semibold text-muted">3</span>
            <span>
              <span class="block text-[0.85rem] font-semibold text-forest">Bayar &amp; pesanan disiapkan</span>
              <span class="mt-0.5 block text-[0.75rem] leading-relaxed text-muted">Instruksi pembayaran ada di bawah, atau kami kirim ulang lewat WhatsApp.</span>
            </span>
          </li>
        </ol>

        <div class="mx-6 mb-5 rounded-[0.75rem] border border-forest/15 bg-forest/[0.045] p-4">
          <div class="flex items-start gap-3">
            <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-forest text-ivory">
              <MessageCircle class="h-4 w-4" :stroke-width="1.8" />
            </span>
            <div class="min-w-0">
              <p class="text-[0.88rem] font-semibold text-forest">Konfirmasi sekarang supaya pesanan tidak tertunda</p>
              <p class="mt-1 text-[0.76rem] leading-relaxed text-muted">
                Admin akan mengecek detail pesanan, mengonfirmasi ongkir atau jadwal antar, lalu mengirim instruksi pembayaran yang sesuai.
              </p>
            </div>
          </div>
          <AppButton size="lg" block class="mt-4" @click="confirmViaWhatsapp">
            <template #icon><MessageCircle class="h-4 w-4" /></template>
            Konfirmasi lewat WhatsApp
          </AppButton>
          <p class="mt-2 text-center text-[0.68rem] leading-relaxed text-muted">Pesan WhatsApp sudah disiapkan dengan nomor pesanan {{ placedOrder.id }}.</p>
        </div>

        <div class="mx-6 mb-5 rounded-[0.75rem] border border-gold/35 bg-gold/[0.07] p-3.5">
          <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-gold/20 text-forest">
              <span class="font-mono text-[0.7rem] font-bold">{{ confirmationTime }}</span>
            </span>
            <div class="min-w-0">
              <p class="text-[0.84rem] font-semibold text-forest">Prioritas konfirmasi {{ confirmationSeconds > 0 ? 'sekarang' : 'tetap tersedia' }}</p>
              <p class="mt-0.5 text-[0.7rem] leading-relaxed text-muted">
                Admin lebih cepat mengecek ongkir, jadwal antar, dan instruksi pembayaran jika Anda langsung mengirim pesan.
              </p>
            </div>
          </div>
          <div class="mt-3 h-1 overflow-hidden rounded-full bg-gold/15" aria-hidden="true">
            <div class="h-full bg-gold transition-[width] duration-1000 ease-linear" :style="{ width: `${Math.min(100, (confirmationSeconds / (15 * 60)) * 100)}%` }" />
          </div>
        </div>

        <dl class="divide-y divide-line border-y border-line px-6 text-[0.87rem]">
          <div v-for="(it, i) in placedOrder.items" :key="i" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">{{ it.name }} × {{ it.qty }}</dt>
            <dd class="flex-none text-forest">{{ formatIDR(it.price * it.qty) }}</dd>
          </div>
          <div v-if="placedOrder.discount > 0" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">Diskon<span v-if="placedOrder.voucherCode" class="text-[0.75rem]"> ({{ placedOrder.voucherCode }})</span></dt>
            <dd class="font-semibold text-olive">-{{ formatIDR(placedOrder.discount) }}</dd>
          </div>
          <div v-if="placedOrder.shipping?.cost" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">Ongkos kirim</dt>
            <dd class="text-forest">{{ formatIDR(placedOrder.shipping.cost) }}</dd>
          </div>
          <div v-if="placedOrder.fulfillment === 'pickup'" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">Pengambilan</dt>
            <dd class="font-semibold text-olive">Ambil di toko</dd>
          </div>
          <div v-if="placedOrder.subtotal" class="flex justify-between gap-6 py-3">
            <dt class="text-muted">Subtotal</dt>
            <dd class="text-forest">{{ formatIDR(placedOrder.subtotal) }}</dd>
          </div>
          <div class="flex items-baseline justify-between gap-6 py-4">
            <dt class="font-semibold text-forest">Total</dt>
            <dd class="font-display text-xl text-forest">{{ formatIDR(placedOrder.total) }}</dd>
          </div>
        </dl>

        <!-- Instruksi bayar manual: transfer/QRIS/COD tanpa payment gateway -->
        <div class="px-6 py-6">
          <div class="rounded-[0.75rem] border border-gold/40 bg-gold/[0.06] p-5">
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-[0.66rem] font-semibold uppercase tracking-[0.16em] text-forest/70">Pembayaran</p>
              <span
                class="rounded-full px-2.5 py-0.5 text-[0.64rem] font-semibold uppercase tracking-[0.08em]"
                :class="placedOrder.paymentStatus === 'paid' ? 'bg-olive/15 text-olive' : 'bg-gold/20 text-forest'"
              >{{ placedOrder.paymentStatus === 'paid' ? 'Lunas' : 'Belum dibayar' }}</span>
            </div>
            <p class="mt-1.5 text-[0.92rem] font-semibold text-forest">{{ placedOrder.payment }}</p>
            <p v-if="placedOrder.paymentStatus !== 'paid'" class="mt-2 text-[0.76rem] leading-relaxed text-forest/80">
              Belum ada pembayaran otomatis. Konfirmasi dulu melalui WhatsApp, lalu ikuti instruksi admin.
            </p>

            <dl
              v-if="placedOrder.paymentStatus !== 'paid' && placedOrder.paymentCode === 'transfer' && store.bankAccountNumber"
              class="mt-3 divide-y divide-gold/25 border-y border-gold/25 text-[0.82rem]"
            >
              <div v-if="store.bankName" class="flex items-center justify-between gap-4 py-2">
                <dt class="text-muted">Bank</dt>
                <dd class="font-medium text-forest">{{ store.bankName }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 py-2">
                <dt class="text-muted">No. rekening</dt>
                <dd class="flex items-center gap-2 font-mono text-[0.9rem] font-semibold tracking-wide text-forest">
                  {{ store.bankAccountNumber }}
                  <button type="button" class="text-muted transition hover:text-forest" aria-label="Salin nomor rekening" @click="copyValue(store.bankAccountNumber, 'Nomor rekening')">
                    <Copy class="h-3.5 w-3.5" :stroke-width="1.6" />
                  </button>
                </dd>
              </div>
              <div v-if="store.bankAccountName" class="flex items-center justify-between gap-4 py-2">
                <dt class="text-muted">Atas nama</dt>
                <dd class="text-forest">{{ store.bankAccountName }}</dd>
              </div>
            </dl>

            <p v-if="placedOrder.paymentStatus !== 'paid'" class="mt-3 whitespace-pre-line text-[0.8rem] leading-relaxed text-forest/90">
              {{ placedOrder.paymentInstructions || paymentExtra }}
            </p>
            <img
              v-if="placedOrder.paymentStatus !== 'paid' && placedOrder.paymentCode === 'qris' && store.qrisImageUrl"
              :src="store.qrisImageUrl" alt="Kode QRIS ArafahGift"
              class="mx-auto mt-4 h-48 w-48 rounded-[0.4rem] border border-line bg-surface object-contain p-2"
            />
          </div>

          <div v-if="placedOrder.fulfillment === 'pickup'" class="mt-4 flex items-start gap-2.5 text-[0.78rem] leading-relaxed text-muted">
            <MapPin class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
            <span>
              <strong class="text-forest">Ambil di toko.</strong>
              {{ store.address || 'Alamat toko dikonfirmasi via WhatsApp.' }}
              <span v-if="store.pickupInstructions" class="mt-1 block">{{ store.pickupInstructions }}</span>
            </span>
          </div>
          <p v-else class="mt-4 flex items-start gap-2.5 text-[0.78rem] leading-relaxed text-muted">
            <MapPin class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" /> {{ placedOrder.address }}
          </p>

          <div class="mt-5 flex flex-wrap items-center justify-center gap-x-5 gap-y-2 text-[0.78rem] text-muted">
            <a v-if="placedOrder.trackingUrl" :href="placedOrder.trackingUrl" class="font-semibold text-forest underline underline-offset-4 transition hover:text-olive">Lacak pesanan</a>
            <Link href="/lacak-pesanan" class="underline underline-offset-4 transition hover:text-forest">Lacak via nomor pesanan</Link>
            <Link href="/" class="transition hover:text-forest">Kembali ke beranda</Link>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="cart.items.value.length" class="min-h-[100dvh]">
      <!-- Header ringkas: logo, rasa aman, bantuan -->
      <header class="sticky top-0 z-30 border-b border-line bg-ivory/85 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3.5 sm:px-10">
          <BrandLogo size="sm" />
          <div class="flex items-center gap-4">
            <span class="hidden items-center gap-1.5 text-[0.72rem] text-muted sm:flex">
              <ShieldCheck class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" /> Data &amp; pembayaran aman
            </span>
            <a :href="helpHref" target="_blank" rel="noopener" class="text-[0.76rem] font-medium text-forest underline underline-offset-4 transition hover:text-olive">Butuh bantuan?</a>
          </div>
        </div>
      </header>

      <div class="mx-auto grid max-w-6xl lg:grid-cols-[minmax(0,1fr)_23rem] xl:grid-cols-[minmax(0,1fr)_25rem]">
        <!-- Form -->
        <div class="min-w-0 px-5 pb-28 pt-8 sm:px-10 sm:pb-20 lg:pb-16 lg:pt-10">
          <div class="flex items-center justify-between gap-4">
            <button type="button" class="flex items-center gap-1.5 text-[0.78rem] text-muted transition hover:text-forest" @click="backToCart">
              <ArrowLeft class="h-3.5 w-3.5" /> Kembali ke keranjang
            </button>
            <span class="text-[0.74rem] text-muted">Langkah {{ step }} dari {{ steps.length }}</span>
          </div>

          <div v-if="serverMessage || serverBanner.length" role="alert" class="mt-5 flex items-start gap-3 rounded-[0.75rem] border border-danger/40 bg-danger/[0.07] px-4 py-3.5 text-[0.82rem] leading-relaxed text-ink">
            <AlertCircle class="mt-0.5 h-4 w-4 flex-none text-danger" :stroke-width="1.5" />
            <p>
              <span v-if="serverMessage">{{ serverMessage }}</span>
              <template v-for="(msg, i) in serverBanner" :key="i">
                <span v-if="i > 0" class="mt-1 block">{{ msg }}</span>
                <span v-else>{{ msg }}</span>
              </template>
            </p>
          </div>

          <!-- Stepper: tab dua langkah, urutan tetap berurutan -->
          <ol class="mt-5 grid grid-cols-2 border-b border-line" aria-label="Langkah checkout">
            <li v-for="s in steps" :key="s.id">
              <button
                type="button"
                class="relative flex w-full items-center gap-2.5 pb-3.5 pr-4 text-left"
                :class="s.id > step ? 'cursor-default' : 'cursor-pointer'"
                :aria-current="step === s.id ? 'step' : undefined"
                :disabled="s.id > step"
                @click="s.id < step && (step = s.id)"
              >
                <span
                  class="grid h-7 w-7 flex-none place-items-center rounded-full border text-[0.72rem] font-semibold transition"
                  :class="step > s.id ? 'border-forest bg-forest text-ivory'
                    : step === s.id ? 'border-gold bg-gold/15 text-forest' : 'border-line bg-surface text-muted'"
                >
                  <Check v-if="step > s.id" class="h-3.5 w-3.5" :stroke-width="2.2" />
                  <template v-else>{{ s.id }}</template>
                </span>
                <span class="text-[0.8rem] font-medium" :class="s.id <= step ? 'text-forest' : 'text-muted'">{{ s.label }}</span>
                <span v-if="step === s.id" class="absolute inset-x-0 -bottom-px h-[2px] bg-gold" />
              </button>
            </li>
          </ol>

          <div v-if="storeClosed" role="alert" class="mt-5 flex items-start gap-3 rounded-[0.75rem] border border-danger/40 bg-danger/[0.07] px-4 py-3.5 text-[0.82rem] leading-relaxed text-ink">
            <AlertCircle class="mt-0.5 h-4 w-4 flex-none text-danger" :stroke-width="1.5" />
            <p>{{ store.closedMessage || 'Toko sedang tutup. Checkout dibuka kembali saat jam operasional.' }}</p>
          </div>
          <div v-else-if="belowMinimum" role="alert" class="mt-5 flex items-start gap-3 rounded-[0.75rem] border border-gold/45 bg-gold/[0.09] px-4 py-3.5 text-[0.82rem] leading-relaxed text-forest">
            <AlertCircle class="mt-0.5 h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
            <p>Minimum belanja {{ formatIDR(minOrderAmount) }}. Tambah {{ formatIDR(minOrderAmount - cart.subtotal.value) }} lagi untuk checkout.</p>
          </div>

        <!-- 1. Data -->
        <section v-if="step === 1" class="mt-8">
          <header>
            <p class="text-[0.64rem] font-semibold uppercase tracking-[0.2em] text-gold">Langkah 1 dari 2</p>
            <h1 class="mt-2.5 text-[1.9rem] leading-[1.08] tracking-[-0.02em] sm:text-[2.15rem]">
              {{ isPickup ? 'Data & pengambilan' : 'Data pengiriman' }}
            </h1>
            <p class="mt-2 max-w-md text-[0.85rem] leading-relaxed text-muted">
              {{ isPickup ? 'Isi data pemesan. Pesanan diambil langsung di toko, tanpa ongkir.' : 'Isi data penerima, lalu pilih kota tujuan untuk melihat kurir dan ongkirnya.' }}
            </p>
          </header>

          <!-- Pilih antar atau ambil sendiri di toko -->
          <div class="mt-7 grid gap-3 sm:grid-cols-2">
            <button
              type="button"
              class="relative flex items-start gap-3 rounded-[0.75rem] border p-4 text-left transition active:translate-y-px"
              :class="!isPickup ? 'border-forest bg-forest/[0.045]' : 'border-line bg-surface hover:border-forest/40 hover:bg-ivory/45'"
              @click="fulfillment = 'delivery'"
            >
              <span class="grid h-10 w-10 flex-none place-items-center rounded-full transition" :class="!isPickup ? 'bg-forest text-ivory' : 'bg-ivory text-forest'">
                <Truck class="h-[18px] w-[18px]" :stroke-width="1.5" />
              </span>
              <span class="min-w-0 flex-1 pr-5">
                <span class="block text-[0.88rem] font-semibold text-forest">Diantar ke alamat</span>
                <span class="mt-0.5 block text-[0.73rem] leading-relaxed text-muted">Ongkir otomatis dari kota tujuan.</span>
              </span>
              <span
                class="absolute right-3.5 top-3.5 grid h-5 w-5 place-items-center rounded-full border transition"
                :class="!isPickup ? 'border-forest bg-forest text-ivory' : 'border-line bg-surface'"
                aria-hidden="true"
              >
                <Check v-if="!isPickup" class="h-3 w-3" :stroke-width="2" />
              </span>
            </button>

            <button
              v-if="pickupEnabled"
              type="button"
              class="relative flex items-start gap-3 rounded-[0.75rem] border p-4 text-left transition active:translate-y-px"
              :class="isPickup ? 'border-forest bg-forest/[0.045]' : 'border-line bg-surface hover:border-forest/40 hover:bg-ivory/45'"
              @click="fulfillment = 'pickup'"
            >
              <span class="grid h-10 w-10 flex-none place-items-center rounded-full transition" :class="isPickup ? 'bg-forest text-ivory' : 'bg-ivory text-forest'">
                <MapPin class="h-[18px] w-[18px]" :stroke-width="1.5" />
              </span>
              <span class="min-w-0 flex-1 pr-5">
                <span class="block text-[0.88rem] font-semibold text-forest">Ambil di tempat</span>
                <span class="mt-0.5 block text-[0.73rem] leading-relaxed text-muted">Tanpa ongkir, ambil langsung di toko.</span>
              </span>
              <span
                class="absolute right-3.5 top-3.5 grid h-5 w-5 place-items-center rounded-full border transition"
                :class="isPickup ? 'border-forest bg-forest text-ivory' : 'border-line bg-surface'"
                aria-hidden="true"
              >
                <Check v-if="isPickup" class="h-3 w-3" :stroke-width="2" />
              </span>
            </button>
          </div>

          <!-- Data pemesan: tamu mengisi sendiri, member terisi otomatis dari akun -->
          <div class="mt-6 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex items-center gap-2.5 border-b border-line px-5 py-4">
              <User class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-[1.05rem] text-forest">Data pemesan</h2>
              <span class="ml-auto text-[0.7rem] text-muted">Konfirmasi via WhatsApp</span>
            </div>
            <div class="grid gap-4 px-5 py-5 sm:grid-cols-2 sm:px-6">
              <div class="sm:col-span-2">
                <label class="field-label" for="name">Nama lengkap</label>
                <input id="name" v-model="form.name" class="field" autocomplete="name" :aria-invalid="!!errors.name" />
                <p v-if="errors.name" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.name }}</p>
              </div>
              <div>
                <label class="field-label" for="phone">Nomor WhatsApp</label>
                <input id="phone" v-model="form.phone" type="tel" inputmode="tel" autocomplete="tel" class="field" placeholder="08xx xxxx xxxx" :aria-invalid="!!errors.phone" />
                <p v-if="errors.phone" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.phone }}</p>
              </div>
              <div>
                <label class="field-label" for="email">Email</label>
                <input id="email" v-model="form.email" type="email" autocomplete="email" class="field" :aria-invalid="!!errors.email" />
                <p v-if="errors.email" class="mt-1.5 text-[0.75rem] text-danger">{{ errors.email }}</p>
              </div>
            </div>
          </div>

          <!-- Alamat pengiriman -->
          <div v-if="!isPickup" class="mt-6 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
              <div class="flex items-center gap-2.5">
                <MapPin class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
                <h2 class="font-display text-[1.05rem] text-forest">Alamat pengiriman</h2>
              </div>
              <button
                v-if="form.street.trim()"
                type="button"
                class="flex-none text-[0.76rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive"
                @click="openAddressModal"
              >
                Ubah
              </button>
            </div>

            <div class="px-5 py-5 sm:px-6">
              <button
                v-if="!form.street.trim()"
                type="button"
                class="group flex w-full items-center gap-4 rounded-[0.6rem] border border-dashed bg-ivory/40 p-5 text-left transition hover:border-forest/50 hover:bg-ivory"
                :class="errors.address || errors.destination ? 'border-danger/60' : 'border-forest/25'"
                @click="openAddressModal"
              >
                <span class="grid h-11 w-11 flex-none place-items-center rounded-full border border-gold/40 bg-gold/[0.08]">
                  <MapPin class="h-5 w-5 text-gold" :stroke-width="1.5" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="block text-[0.9rem] font-semibold text-forest">Tambah alamat pengiriman</span>
                  <span class="mt-0.5 block text-[0.76rem] leading-relaxed text-muted">Dipakai untuk menghitung ongkir dan mengirim pesanan.</span>
                </span>
                <ChevronRight class="h-4 w-4 flex-none text-muted transition group-hover:translate-x-0.5 group-hover:text-forest" :stroke-width="1.6" />
              </button>
              <p v-if="!form.street.trim() && (errors.address || errors.destination)" class="mt-3 flex items-start gap-2 text-[0.78rem] font-medium text-danger" role="alert">
                <AlertCircle class="mt-0.5 h-3.5 w-3.5 flex-none" :stroke-width="1.5" />
                {{ errors.address || errors.destination }}
              </p>

              <template v-else>
                <p class="text-[0.9rem] leading-relaxed text-forest">{{ fullAddress }}</p>
                <p class="mt-1 text-[0.78rem] text-muted">{{ form.city }}{{ form.postal ? ` ${form.postal}` : '' }}{{ form.province ? `, ${form.province}` : '' }}</p>
                <p v-if="errors.address || errors.destination" class="mt-2 text-[0.78rem] text-danger">
                  {{ errors.address || errors.destination }}
                </p>
              </template>
            </div>

            <!-- Gratis ongkir & pilihan kurir (tampil setelah alamat tersimpan) -->
            <div v-if="form.street.trim()" class="border-t border-line px-5 py-5 sm:px-6">
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                  <h3 class="text-[0.68rem] font-semibold uppercase tracking-[0.14em] text-forest/70">Pengiriman</h3>
                  <p class="mt-1 text-[0.92rem] font-semibold text-forest">Pilih kurir Anda</p>
                </div>
                <span v-if="shippingWeight" class="text-[0.72rem] text-muted">Berat {{ weightText(shippingWeight) }}</span>
              </div>

              <p v-if="!hasFreeShipping" class="mt-3 rounded-[0.5rem] border border-gold/25 bg-gold/[0.06] px-3.5 py-3 text-[0.73rem] leading-relaxed text-muted">
                Ongkir ditampilkan berdasarkan berat produk dan tujuan yang Anda pilih. Estimasi waktu dapat berubah mengikuti proses sortir dan kondisi kurir.
              </p>
              <p class="mt-3 rounded-[0.5rem] border border-forest/20 bg-forest/[0.04] px-3.5 py-3 text-[0.73rem] leading-relaxed text-forest">
                <template v-if="isMakassar">Kurir ArafahGift gratis untuk Kota Makassar. Pengantaran {{ store.arafagiftDeliveryStart ?? '13:00' }}-{{ store.arafagiftDeliveryEnd ?? '16:00' }} WITA, lalu dikonfirmasi admin.</template>
                <template v-else>Kurir ArafahGift tersedia di luar Makassar. Biaya dan jadwal dikonfirmasi melalui WhatsApp.</template>
              </p>
              <p v-if="hasFreeShipping" class="mt-3 flex items-center gap-2 rounded-[0.5rem] border border-olive/30 bg-olive/[0.07] px-3.5 py-3 text-[0.8rem] text-olive">
                <Truck class="h-4 w-4 flex-none" :stroke-width="1.5" /> Gratis ongkir untuk pesanan ini.
              </p>
              <button
                v-if="deliveryCityReady"
                type="button"
                class="mt-4 flex w-full items-center justify-between gap-4 rounded-[0.65rem] border border-forest/25 bg-surface px-4 py-3.5 text-left transition hover:border-forest/50 hover:bg-ivory active:translate-y-px"
                @click="changeDeliveryMethod"
              >
                <span class="min-w-0">
                  <span class="block text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-muted">Pesanan diantar dengan</span>
                  <span v-if="deliveryMethod === 'arafagift'" class="mt-1 block truncate text-[0.88rem] font-semibold text-forest">Kurir ArafahGift <span class="font-normal text-olive">{{ isMakassar ? '· Gratis Makassar' : '· Konfirmasi admin' }}</span></span>
                  <span v-else-if="deliveryMethod === 'expedition'" class="mt-1 block truncate text-[0.88rem] font-semibold text-forest">Kurir ekspedisi</span>
                  <span v-else class="mt-1 block text-[0.88rem] font-semibold text-muted">Pilih metode pengantaran</span>
                </span>
                <span class="flex-none text-[0.72rem] font-semibold text-forest underline underline-offset-4">Ubah</span>
              </button>

              <template v-if="deliveryCityReady && deliveryMethod">
                <div v-if="shippingLoading" class="mt-3 space-y-2.5" aria-live="polite">
                  <p class="text-[0.78rem] font-medium text-forest">Mencari pilihan pengiriman</p>
                  <span v-for="i in 3" :key="i" class="motion-safe:animate-pulse block h-14 rounded-[0.5rem] bg-line/40" />
                </div>
                <div v-if="deliveryMethod === 'expedition' && shippingUnavailable && !isMakassar" class="mt-3 rounded-[0.5rem] border border-dashed border-line bg-ivory/40 p-4 text-[0.8rem] leading-relaxed text-muted">
                  <p>Ongkos kirim belum bisa dihitung otomatis untuk tujuan ini. Layanan kurir sedang terganggu atau tujuan belum tercakup, dan kami dapat mengonfirmasikannya melalui WhatsApp.</p>
                  <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-2">
                    <button type="button" class="font-semibold text-forest underline underline-offset-4 transition hover:text-olive active:translate-y-px" @click="fetchShipping">
                      Coba hitung lagi
                    </button>
                    <a
                      :href="whatsappHref(`Halo ArafahGift, tolong bantu hitung ongkir ke ${form.city.trim() || 'kota tujuan saya'}. Alamat: ${fullAddress}.`)"
                      target="_blank" rel="noopener"
                      class="font-semibold text-forest underline underline-offset-4 transition hover:text-olive"
                    >Minta bantuan hitung ongkir</a>
                  </div>
                </div>
                <div v-if="shippingError" class="mt-3 rounded-[0.5rem] border border-danger/35 bg-danger/[0.06] p-4 text-[0.8rem] leading-relaxed text-ink">
                  <p>{{ shippingError }}</p>
                  <Link href="/koleksi" class="mt-2 inline-block font-semibold text-forest underline underline-offset-4 transition hover:text-olive">Periksa keranjang Anda</Link>
                </div>
                <div v-if="!shippingLoading && shippingGroups.length" class="mt-3 space-y-4">
                  <p class="text-[0.75rem] font-medium text-muted">Pilih salah satu layanan pengiriman untuk melanjutkan.</p>
                  <div v-for="group in shippingGroups" :key="group.code" class="overflow-hidden rounded-[0.65rem] border border-line bg-surface">
                    <button
                      type="button"
                      class="flex w-full items-center gap-3 bg-ivory/55 px-3.5 py-3 text-left transition hover:bg-ivory"
                      :aria-expanded="isShippingGroupOpen(group.code)"
                      @click="toggleShippingGroup(group.code)"
                    >
                      <span class="grid h-8 w-8 flex-none place-items-center rounded-full bg-forest text-[0.65rem] font-bold uppercase text-ivory">
                        {{ group.name.slice(0, 2) }}
                      </span>
                      <div class="min-w-0 flex-1">
                        <p class="truncate text-[0.82rem] font-semibold text-forest">{{ group.name }}</p>
                        <p class="mt-0.5 text-[0.66rem] text-muted">{{ group.options.length }} layanan tersedia</p>
                      </div>
                      <ChevronUp class="h-4 w-4 flex-none text-muted transition-transform" :class="isShippingGroupOpen(group.code) ? '' : 'rotate-180'" :stroke-width="1.7" />
                    </button>
                    <div v-if="isShippingGroupOpen(group.code)" class="space-y-2 border-t border-line p-2.5">
                      <label
                        v-for="opt in group.options"
                        :key="`${opt.courier}-${opt.service}-${opt.cost}`"
                        class="flex cursor-pointer items-start gap-3 rounded-[0.55rem] border px-3 py-3 transition active:translate-y-px"
                        :class="selectedShipping === opt ? 'border-forest bg-forest/[0.055] shadow-[inset_3px_0_0_rgb(var(--c-forest))]' : 'border-line bg-ivory/35 hover:border-forest/35 hover:bg-ivory'"
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
                          <span class="flex flex-wrap items-center gap-1.5">
                            <span class="text-[0.84rem] font-semibold text-forest">{{ opt.manual ? 'Antar oleh tim ArafahGift' : opt.service }}</span>
                            <span v-if="opt.manual" class="rounded-full border border-forest/20 bg-forest/10 px-2 py-0.5 text-[0.58rem] font-semibold uppercase tracking-[0.08em] text-forest">Manual</span>
                            <span
                              v-for="badge in shippingBadges(opt)"
                              :key="badge"
                              class="rounded-full border border-gold/35 bg-gold/[0.09] px-2 py-0.5 text-[0.6rem] font-semibold uppercase tracking-[0.07em] text-forest"
                            >{{ badge }}</span>
                          </span>
                          <span class="mt-1 block text-[0.71rem] leading-relaxed text-muted">{{ opt.description || 'Layanan reguler' }}</span>
                        </span>
                        <span class="flex-none text-right">
                          <span class="block text-[0.78rem] font-semibold leading-tight text-forest">{{ opt.manual ? 'Konfirmasi admin' : formatIDR(opt.cost) }}</span>
                          <span class="mt-1 block text-[0.66rem] leading-tight text-muted">{{ opt.manual ? 'Via WhatsApp' : etdText(opt.etd) }}</span>
                        </span>
                      </label>
                    </div>
                  </div>
                  <p class="border-t border-line pt-3 text-[0.7rem] leading-relaxed text-muted">
                    Catatan: tarif dan estimasi bersifat perkiraan dari sistem kurir. Admin akan mengonfirmasi pesanan melalui WhatsApp sebelum diproses.
                  </p>
                </div>
              </template>
            </div>
          </div>

          <!-- Ambil di toko: tanpa alamat & ongkir -->
          <div v-else class="mt-6 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex items-center gap-2.5 border-b border-line px-5 py-4">
              <MapPin class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-[1.05rem] text-forest">Lokasi pengambilan</h2>
              <span class="ml-auto rounded-full border border-olive/40 bg-olive/10 px-2.5 py-0.5 text-[0.62rem] font-semibold uppercase tracking-[0.06em] text-olive">Tanpa ongkir</span>
            </div>
            <div class="px-5 py-5 sm:px-6">
              <p class="text-[0.9rem] leading-relaxed text-forest">{{ store.address || 'Alamat toko dikonfirmasi melalui WhatsApp.' }}</p>
              <p v-if="store.businessHours" class="mt-1 text-[0.79rem] text-muted">Jam buka: {{ store.businessHours }}</p>
              <p v-if="store.pickupInstructions" class="mt-4 whitespace-pre-line rounded-[0.5rem] border border-gold/30 bg-gold/[0.06] px-3.5 py-3 text-[0.8rem] leading-relaxed text-forest">{{ store.pickupInstructions }}</p>
              <p class="mt-4 text-[0.76rem] leading-relaxed text-muted">Kami kabari lewat WhatsApp begitu pesanan siap diambil.</p>
            </div>
          </div>

          <!-- Kartu ucapan & catatan -->
          <div class="mt-6 overflow-hidden rounded-[0.75rem] border border-gold/35 bg-gold/[0.05]">
            <div class="flex items-center gap-2.5 border-b border-gold/25 px-5 py-4">
              <Gift class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-[1.05rem] text-forest">Kartu ucapan</h2>
              <span class="ml-auto rounded-full border border-olive/40 bg-olive/10 px-2.5 py-0.5 text-[0.62rem] font-semibold uppercase tracking-[0.06em] text-olive">Gratis</span>
            </div>
            <div class="px-5 py-5 sm:px-6">
              <label class="sr-only" for="giftmsg">Pesan kartu ucapan</label>
              <textarea
                id="giftmsg" v-model="form.giftMessage" rows="3" maxlength="180"
                class="field rounded-lg bg-surface" placeholder="Contoh: Untuk Ibu, dari tanah suci. Doa kami sampai duluan."
              />
              <div class="mt-2 flex items-center justify-between gap-3">
                <label class="flex items-center gap-2 text-[0.77rem] text-forest">
                  <input v-model="form.hideInvoice" type="checkbox" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
                  Sembunyikan nota harga di dalam paket
                </label>
                <span class="flex-none text-[0.7rem] tabular-nums text-muted">{{ form.giftMessage.length }}/180</span>
              </div>
            </div>
            <div class="border-t border-gold/25 px-5 py-5 sm:px-6">
              <label class="field-label" for="note">Catatan pesanan <span class="font-normal text-muted">(opsional)</span></label>
              <textarea
                id="note" v-model="form.note" rows="2" maxlength="500"
                class="field rounded-lg bg-surface" placeholder="Contoh: tolong kirim sore hari, atau titip pesan ke kurir"
              />
            </div>
          </div>
        </section>

        <!-- 2. Tinjau -->
        <section v-else class="mt-8">
          <header>
            <p class="text-[0.64rem] font-semibold uppercase tracking-[0.2em] text-gold">Langkah 2 dari 2</p>
            <h1 class="mt-2.5 text-[1.9rem] leading-[1.08] tracking-[-0.02em] sm:text-[2.15rem]">Tinjau pesanan</h1>
            <p class="mt-2 max-w-md text-[0.85rem] leading-relaxed text-muted">Periksa sekali lagi. Setelah itu pesanan dikirim ke kami lewat WhatsApp.</p>
          </header>

          <!-- Detail pesanan -->
          <div class="mt-7 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex items-center justify-between gap-4 border-b border-line px-5 py-4">
              <div class="flex items-center gap-2.5">
                <User class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
                <h2 class="font-display text-[1.05rem] text-forest">Detail pesanan</h2>
              </div>
              <button type="button" class="flex-none text-[0.76rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive" @click="editStep">
                Ubah
              </button>
            </div>
            <dl class="divide-y divide-line px-5 text-[0.86rem] sm:px-6">
              <div class="flex justify-between gap-6 py-3.5">
                <dt class="flex-none text-muted">Pemesan</dt>
                <dd class="text-right text-forest">
                  {{ form.name }}
                  <span class="block text-[0.76rem] text-muted">{{ form.phone }}<template v-if="form.email"> · {{ form.email }}</template></span>
                  <span v-if="errors.name || errors.phone || errors.email" class="mt-1 block text-[0.75rem] text-danger">{{ errors.name || errors.phone || errors.email }}</span>
                </dd>
              </div>
              <div class="flex justify-between gap-6 py-3.5">
                <dt class="flex-none text-muted">{{ isPickup ? 'Ambil di' : 'Kirim ke' }}</dt>
                <dd class="max-w-xs text-right text-forest">
                  <template v-if="isPickup">{{ store.address || 'Toko ArafahGift' }}</template>
                  <template v-else>{{ fullAddress }}, {{ form.city }} {{ form.postal }}</template>
                </dd>
              </div>
              <div v-if="selectedShipping && !isPickup" class="flex justify-between gap-6 py-3.5">
                <dt class="flex-none text-muted">Kurir</dt>
                <dd class="max-w-xs text-right text-forest">
                  {{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }}
                  <span class="block text-[0.77rem] font-medium">{{ selectedShipping.service }}</span>
                  <span class="mt-0.5 block text-[0.72rem] text-muted">{{ etdText(selectedShipping.etd) }}</span>
                  <span v-if="shippingWeight" class="block text-[0.72rem] text-muted">Berat {{ weightText(shippingWeight) }}</span>
                </dd>
              </div>
              <div v-if="form.giftMessage" class="flex justify-between gap-6 py-3.5">
                <dt class="flex-none text-muted">Kartu ucapan</dt>
                <dd class="max-w-xs text-right font-display text-[1rem] italic text-forest">“{{ form.giftMessage }}”</dd>
              </div>
            </dl>
          </div>

          <!-- Pembayaran manual: tanpa payment gateway -->
          <div class="mt-6 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex flex-wrap items-center gap-2.5 border-b border-line px-5 py-4">
              <CreditCard class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-[1.05rem] text-forest">Metode pembayaran</h2>
              <span class="ml-auto text-[0.68rem] text-muted">Konfirmasi via WhatsApp</span>
            </div>
            <div class="px-5 py-5 sm:px-6">
              <div class="grid gap-2.5 sm:grid-cols-2">
                <label
                  v-for="method in paymentMethods"
                  :key="method.id"
                  class="relative flex cursor-pointer items-start gap-3 rounded-[0.6rem] border p-3.5 transition active:translate-y-px"
                  :class="paymentMethod === method.id ? 'border-forest bg-forest/[0.045]' : 'border-line bg-surface hover:border-forest/35 hover:bg-ivory/45'"
                >
                  <input v-model="paymentMethod" type="radio" name="payment-method" class="sr-only" :value="method.id" />
                  <span
                    class="grid h-10 w-10 flex-none place-items-center rounded-full border transition"
                    :class="paymentMethod === method.id ? 'border-forest bg-forest text-ivory' : 'border-line bg-ivory text-forest'"
                    aria-hidden="true"
                  >
                    <component :is="paymentIcon(method.id)" class="h-[18px] w-[18px]" :stroke-width="1.5" />
                  </span>
                  <span class="min-w-0 flex-1 pr-4">
                    <span class="block text-[0.84rem] font-semibold text-forest">{{ method.name }}</span>
                    <span v-if="method.note" class="mt-0.5 block text-[0.71rem] leading-relaxed text-muted">{{ method.note }}</span>
                  </span>
                  <span
                    class="absolute right-3 top-3 grid h-5 w-5 place-items-center rounded-full border transition"
                    :class="paymentMethod === method.id ? 'border-forest bg-forest text-ivory' : 'border-line bg-surface'"
                    aria-hidden="true"
                  >
                    <Check v-if="paymentMethod === method.id" class="h-3 w-3" :stroke-width="2.5" />
                  </span>
                </label>
              </div>
              <p v-if="errors.payment" class="mt-2 text-[0.78rem] text-danger">{{ errors.payment }}</p>

              <div v-if="selectedPayment" class="mt-4 rounded-[0.6rem] border border-dashed border-gold/45 bg-gold/[0.055] p-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <p class="text-[0.66rem] font-semibold uppercase tracking-[0.14em] text-forest/70">Instruksi pembayaran</p>
                  <p class="text-[0.68rem] text-muted">{{ selectedPayment.name }}</p>
                </div>

                <dl
                  v-if="selectedPayment.id === 'transfer' && store.bankAccountNumber"
                  class="mt-3 divide-y divide-gold/25 border-y border-gold/25 text-[0.82rem]"
                >
                  <div v-if="store.bankName" class="flex items-center justify-between gap-4 py-2">
                    <dt class="text-muted">Bank</dt>
                    <dd class="font-medium text-forest">{{ store.bankName }}</dd>
                  </div>
                  <div class="flex items-center justify-between gap-4 py-2">
                    <dt class="text-muted">No. rekening</dt>
                    <dd class="flex items-center gap-2 font-mono text-[0.9rem] font-semibold tracking-wide text-forest">
                      {{ store.bankAccountNumber }}
                      <button type="button" class="text-muted transition hover:text-forest" aria-label="Salin nomor rekening" @click="copyValue(store.bankAccountNumber, 'Nomor rekening')">
                        <Copy class="h-3.5 w-3.5" :stroke-width="1.6" />
                      </button>
                    </dd>
                  </div>
                  <div v-if="store.bankAccountName" class="flex items-center justify-between gap-4 py-2">
                    <dt class="text-muted">Atas nama</dt>
                    <dd class="text-forest">{{ store.bankAccountName }}</dd>
                  </div>
                </dl>

                <p v-if="paymentExtra" class="mt-3 whitespace-pre-line text-[0.8rem] leading-relaxed text-forest">{{ paymentExtra }}</p>
                <img
                  v-if="selectedPayment.id === 'qris' && store.qrisImageUrl"
                  :src="store.qrisImageUrl" alt="Kode QRIS ArafahGift"
                  class="mx-auto mt-4 h-48 w-48 rounded-[0.4rem] border border-line bg-surface object-contain p-2"
                />
                <p class="mt-3 border-t border-gold/25 pt-3 text-[0.71rem] leading-relaxed text-muted">
                  <template v-if="selectedPayment.id === 'cod'">Bayar tunai saat pesanan diterima. Siapkan uang pas bila memungkinkan.</template>
                  <template v-else>Sudah bayar? Kirim bukti pembayaran lewat WhatsApp agar pesanan segera diproses.</template>
                </p>
              </div>
            </div>
          </div>

          <!-- Voucher -->
          <div class="mt-6 overflow-hidden rounded-[0.75rem] border border-line bg-surface">
            <div class="flex items-center gap-2.5 border-b border-line px-5 py-4">
              <TicketPercent class="h-4 w-4 flex-none text-gold" :stroke-width="1.5" />
              <h2 class="font-display text-[1.05rem] text-forest">Kode voucher</h2>
              <span class="ml-auto text-[0.68rem] text-muted">Opsional</span>
            </div>
            <div class="px-5 py-5 sm:px-6">
              <div v-if="!voucher" class="flex flex-col gap-2 sm:flex-row">
                <input
                  v-model="voucherCode"
                  class="field flex-1 rounded-lg uppercase tracking-wide"
                  placeholder="Contoh: HEMAT10"
                  autocomplete="off"
                  @keyup.enter.prevent="applyVoucher"
                />
                <AppButton variant="outline" size="md" :loading="voucherLoading" class="sm:flex-none" @click="applyVoucher">Terapkan</AppButton>
              </div>
              <div v-else class="flex flex-wrap items-center justify-between gap-3 rounded-[0.55rem] border border-olive/30 bg-olive/[0.07] px-4 py-3">
                <p class="flex items-center gap-2 text-[0.82rem] text-forest">
                  <Check class="h-3.5 w-3.5 flex-none text-olive" :stroke-width="2.5" />
                  <span class="font-mono font-semibold">{{ voucher.code }}</span>
                  <span class="text-olive">· hemat {{ formatIDR(voucher.discount) }}</span>
                </p>
                <button type="button" class="text-[0.74rem] font-semibold text-forest underline underline-offset-4 transition hover:text-olive" @click="removeVoucher">Hapus</button>
              </div>
              <p v-if="voucherError" class="mt-2 text-[0.78rem] text-danger">{{ voucherError }}</p>
              <p v-if="!voucher && !voucherError" class="mt-2 text-[0.72rem] text-muted">Punya kode promo? Tempel di sini sebelum membuat pesanan.</p>
            </div>
          </div>
        </section>

        <!-- Modal pilihan jalur pengiriman -->
        <Teleport to="body">
          <Transition
            enter-active-class="transition duration-200 ease-calm"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-calm"
            leave-to-class="opacity-0"
          >
            <div v-if="shippingChoiceOpen" class="fixed inset-0 z-[210] flex items-end justify-center bg-forest-deep/50 p-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur-sm sm:items-center sm:p-4" @click.self="shippingChoiceOpen = false">
              <div class="max-h-[85dvh] w-full max-w-md overflow-y-auto rounded-[0.8rem] border border-line bg-ivory shadow-lift">
                <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-5">
                  <div>
                    <p class="text-[0.62rem] font-semibold uppercase tracking-[0.16em] text-gold">Pilihan pengantaran</p>
                    <h2 class="mt-1 font-display text-[1.4rem] leading-tight text-forest">Pesanan mau diantar pakai apa?</h2>
                    <p class="mt-1.5 text-[0.76rem] leading-relaxed text-muted">Pilih jalur pengantaran. Detail layanan ekspedisi bisa dipilih setelahnya.</p>
                  </div>
                  <button type="button" class="grid h-8 w-8 flex-none place-items-center text-muted transition hover:text-forest" aria-label="Tutup pilihan pengantaran" @click="shippingChoiceOpen = false">
                    <X class="h-4 w-4" />
                  </button>
                </header>

                <div class="space-y-2.5 px-5 py-5">
                  <button
                    type="button"
                    class="w-full rounded-[0.65rem] border p-4 text-left transition active:translate-y-px"
                    :class="deliveryMethod === 'arafagift' ? 'border-forest bg-forest/[0.055]' : 'border-line bg-surface hover:border-forest/40 hover:bg-ivory'"
                    @click="chooseDeliveryMethod('arafagift')"
                  >
                    <span class="flex items-start gap-3">
                      <span class="grid h-9 w-9 flex-none place-items-center rounded-full bg-forest text-[0.7rem] font-bold text-ivory">AG</span>
                      <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-3">
                          <span class="font-semibold text-forest">Kurir ArafahGift</span>
                          <Check v-if="deliveryMethod === 'arafagift'" class="h-4 w-4 flex-none text-forest" :stroke-width="2" />
                        </span>
                        <span class="mt-1 block text-[0.74rem] leading-relaxed text-muted">
                          <template v-if="isMakassar">Rekomendasi untuk Anda. Gratis seluruh Kota Makassar, {{ store.arafagiftDeliveryStart ?? '13:00' }}-{{ store.arafagiftDeliveryEnd ?? '16:00' }} WITA.</template>
                          <template v-else>Biaya dan jadwal dikonfirmasi admin melalui WhatsApp.</template>
                        </span>
                      </span>
                    </span>
                  </button>

                  <button
                    type="button"
                    class="w-full rounded-[0.65rem] border p-4 text-left transition active:translate-y-px"
                    :class="deliveryMethod === 'expedition' ? 'border-forest bg-forest/[0.055]' : 'border-line bg-surface hover:border-forest/40 hover:bg-ivory'"
                    @click="chooseDeliveryMethod('expedition')"
                  >
                    <span class="flex items-start gap-3">
                      <span class="grid h-9 w-9 flex-none place-items-center rounded-full border border-line bg-ivory text-[0.7rem] font-bold text-forest">EX</span>
                      <span class="min-w-0 flex-1">
                        <span class="flex items-center justify-between gap-3">
                          <span class="font-semibold text-forest">Kurir ekspedisi</span>
                          <Check v-if="deliveryMethod === 'expedition'" class="h-4 w-4 flex-none text-forest" :stroke-width="2" />
                        </span>
                        <span class="mt-1 block text-[0.74rem] leading-relaxed text-muted">Pilih layanan SiCepat, J&T, JNE, dan ekspedisi yang tersedia.</span>
                      </span>
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </Transition>
        </Teleport>

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
                          ref="destinationRef"
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
                  <MapPinPicker v-model="form.pin" @address="applyLocation" />
                </div>

                <footer class="flex border-t border-line px-5 py-4 sm:px-6">
                  <AppButton size="lg" class="w-full" @click="saveAddress">Simpan alamat</AppButton>
                </footer>
              </div>
            </div>
          </Transition>
        </Teleport>

        <!-- Bar aksi: menempel di bawah pada layar kecil, statis di desktop -->
        <div class="sticky bottom-0 z-30 -mx-5 mt-8 flex items-center gap-2.5 border-t border-line bg-ivory/95 px-5 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))] backdrop-blur sm:-mx-10 sm:px-10 lg:static lg:mx-0 lg:mt-8 lg:justify-end lg:border-0 lg:bg-transparent lg:p-0 lg:backdrop-blur-none">
          <!-- Ringkasan singkat: ketuk untuk membuka lembar ringkasan -->
          <button
            type="button"
            class="group min-w-0 flex-1 text-left lg:hidden"
            aria-label="Buka ringkasan pesanan"
            @click="summaryOpen = true"
          >
            <span class="flex items-center gap-1 text-[0.6rem] font-semibold uppercase tracking-[0.14em] text-muted">
              {{ shippingCostDisplay !== null ? 'Total' : 'Estimasi' }}
              <ChevronUp class="h-3 w-3 text-gold transition group-hover:-translate-y-0.5" :stroke-width="2" />
            </span>
            <span class="mt-0.5 block truncate font-display text-[1.15rem] leading-none text-forest">{{ formatIDR(grandTotal) }}</span>
            <span class="mt-0.5 block text-[0.66rem] text-muted">{{ cart.count.value }} pcs · lihat rincian</span>
          </button>

          <AppButton v-if="step > 1" variant="quiet" size="md" class="flex-none px-4" aria-label="Kembali ke langkah sebelumnya" @click="back">
            <template #icon><ArrowLeft class="h-4 w-4" :stroke-width="1.6" /></template>
            <span class="hidden sm:inline">Kembali</span>
          </AppButton>
          <AppButton v-if="step < steps.length" size="md" class="flex-none px-6" :disabled="shippingLoading || (storeClosed && step === 1)" @click="next">
            Lanjut
          </AppButton>
          <AppButton v-else size="md" class="flex-none px-5" :loading="submitting" :disabled="shippingLoading || storeClosed || belowMinimum || !paymentMethod" @click="placeOrder">
            Buat pesanan
          </AppButton>
        </div>

        <p class="mt-5 flex items-start gap-2 pb-4 text-[0.72rem] leading-relaxed text-muted lg:pb-0">
          <ShieldCheck class="mt-px h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" />
          Pembayaran manual (QRIS, transfer, atau COD). Instruksi dikirim di halaman ini dan via WhatsApp.
        </p>
      </div>

      <!-- Ringkasan: panel nota yang menempel, sejajar dengan kartu step di kiri -->
      <aside class="hidden lg:sticky lg:top-[3.6rem] lg:block lg:h-[calc(100dvh-3.6rem)] lg:overflow-y-auto lg:border-l lg:border-line lg:px-6 lg:py-8 xl:px-7">
        <div class="overflow-hidden rounded-[0.75rem] border border-line bg-surface shadow-soft">
          <header class="flex items-center justify-between gap-3 border-b border-line bg-ivory/55 px-5 py-4">
            <div>
              <p class="text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-gold">Pesanan Anda</p>
              <h2 class="mt-1 font-display text-[1.15rem] leading-none text-forest">Ringkasan</h2>
            </div>
            <span class="rounded-full border border-line bg-surface px-2.5 py-1 text-[0.68rem] font-semibold text-forest">{{ cart.count.value }} pcs</span>
          </header>

          <ul class="max-h-[16rem] divide-y divide-line overflow-y-auto px-5">
            <li v-for="item in cart.items.value" :key="item.id" class="flex items-center gap-3 py-3.5">
              <div class="relative flex-none">
                <span class="block h-14 w-12 overflow-hidden rounded-[0.3rem] border border-line bg-ivory/60">
                  <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
                  <ProductArt v-else :art="item.art" :tone="item.id" />
                </span>
                <span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-forest px-1 text-[0.62rem] font-semibold text-ivory">{{ item.qty }}</span>
              </div>
              <div class="min-w-0 flex-1">
                <p class="truncate text-[0.82rem] font-medium leading-snug text-forest">{{ item.name }}</p>
                <p class="mt-0.5 text-[0.7rem] text-muted">{{ formatIDR(item.price) }} × {{ item.qty }}</p>
              </div>
              <p class="flex-none text-[0.8rem] font-semibold text-forest">{{ formatIDR(item.lineTotal) }}</p>
            </li>
          </ul>

          <dl class="space-y-2.5 border-t border-line px-5 py-4 text-[0.82rem]">
            <div class="flex items-start justify-between gap-4">
              <dt class="text-muted">Subtotal <span class="text-[0.68rem]">({{ cart.count.value }} pcs)</span></dt>
              <dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd>
            </div>
            <div class="flex items-start justify-between gap-4">
              <dt class="text-muted">{{ isPickup ? 'Pengambilan' : 'Ongkos kirim' }}</dt>
              <dd class="text-right">
                <template v-if="isPickup">
                  <span class="inline-block rounded-full border border-olive/35 bg-olive/10 px-2 py-0.5 text-[0.68rem] font-semibold text-olive">Ambil di toko</span>
                </template>
                <template v-else-if="hasFreeShipping">
                  <span class="inline-block rounded-full border border-olive/35 bg-olive/10 px-2 py-0.5 text-[0.68rem] font-semibold text-olive">Gratis</span>
                </template>
                <template v-else-if="selectedShipping">
                  <span class="font-semibold text-forest">{{ selectedShipping.manual ? 'Konfirmasi admin' : formatIDR(selectedShipping.cost) }}</span>
                  <span class="mt-0.5 block text-[0.68rem] text-muted">
                    {{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }} · {{ selectedShipping.service }}
                  </span>
                  <span class="block text-[0.66rem] text-muted/80">{{ selectedShipping.manual ? 'Via WhatsApp' : etdText(selectedShipping.etd) }}</span>
                </template>
                <template v-else-if="shippingLoading">
                  <span class="mt-0.5 block h-3.5 w-20 rounded bg-line motion-safe:animate-pulse" />
                </template>
                <template v-else-if="manualCity || form.destinationId">
                  <span class="inline-block rounded-full border border-gold/40 bg-gold/[0.09] px-2 py-0.5 text-[0.66rem] font-medium text-forest">Via WhatsApp</span>
                </template>
                <template v-else><span class="text-[0.76rem] text-muted">Isi kota tujuan dulu</span></template>
              </dd>
            </div>
            <div v-if="voucher" class="flex items-start justify-between gap-4">
              <dt class="text-muted">Diskon <span class="text-[0.68rem]">({{ voucher.code }})</span></dt>
              <dd class="font-semibold text-olive">−{{ formatIDR(voucher.discount) }}</dd>
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

          <div class="bg-forest-deep px-5 py-4 text-ivory">
            <div class="flex items-end justify-between gap-4">
              <span class="text-[0.72rem] leading-snug text-ivory/65">
                <template v-if="shippingCostDisplay !== null">Total bayar</template>
                <template v-else>Estimasi total <span class="mt-0.5 block text-[0.64rem] text-ivory/45">belum termasuk ongkir</span></template>
              </span>
              <span class="font-display text-[1.55rem] leading-none tracking-[-0.02em]">{{ formatIDR(grandTotal) }}</span>
            </div>
          </div>

          <div class="flex items-start gap-2 px-5 py-3.5 text-[0.7rem] leading-relaxed text-muted">
            <ShieldCheck class="mt-px h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" />
            <span>Tanpa pembayaran otomatis. Pesanan dikonfirmasi lewat WhatsApp bersama admin kami.</span>
          </div>
        </div>

        <a :href="helpHref" target="_blank" rel="noopener" class="mt-4 flex items-center justify-center gap-1.5 text-[0.74rem] text-muted transition hover:text-forest">
          <MessageCircle class="h-3.5 w-3.5" :stroke-width="1.5" /> Butuh bantuan memesan?
        </a>
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
            <header class="relative overflow-hidden border-b border-forest-soft/20 bg-forest-deep px-6 pb-4 pt-4">
              <span class="pointer-events-none absolute -right-10 -top-12 h-32 w-32 rounded-full border border-gold/15" aria-hidden="true" />
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-gold">Pesanan Anda</p>
                  <h2 class="mt-1 font-display text-[1.35rem] leading-none text-ivory">
                    Ringkasan
                    <span class="ml-1 align-middle text-[0.8rem] font-normal text-ivory/50">{{ cart.count.value }} pcs</span>
                  </h2>
                </div>
                <button class="grid h-9 w-9 flex-none place-items-center rounded-full border border-ivory/20 text-ivory/70 transition hover:border-ivory/40 hover:text-ivory" aria-label="Tutup ringkasan" @click="summaryOpen = false">
                  <X class="h-[18px] w-[18px]" :stroke-width="1.5" />
                </button>
              </div>
            </header>

            <div class="flex-1 overflow-y-auto px-5 py-5">
              <ul class="divide-y divide-line rounded-[0.65rem] border border-line bg-surface px-4">
                <li v-for="item in cart.items.value" :key="item.id" class="flex items-center gap-3 py-3.5">
                  <div class="relative flex-none">
                    <span class="block h-14 w-12 overflow-hidden rounded-[0.3rem] border border-line bg-ivory/60">
                      <img v-if="item.image" :src="item.image" :alt="item.name" class="h-full w-full object-cover" />
                      <ProductArt v-else :art="item.art" :tone="item.id" />
                    </span>
                    <span class="absolute -right-1.5 -top-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-forest px-1 text-[0.62rem] font-semibold text-ivory">{{ item.qty }}</span>
                  </div>
                  <div class="min-w-0 flex-1">
                    <p class="truncate text-[0.84rem] font-medium leading-snug text-forest">{{ item.name }}</p>
                    <p class="mt-0.5 text-[0.7rem] text-muted">{{ formatIDR(item.price) }} × {{ item.qty }}</p>
                  </div>
                  <p class="flex-none text-[0.82rem] font-semibold text-forest">{{ formatIDR(item.lineTotal) }}</p>
                </li>
              </ul>

              <dl class="mt-5 space-y-2.5 border-t border-line px-0 pt-4 text-[0.82rem]">
                <div class="flex items-start justify-between gap-4">
                  <dt class="text-muted">Subtotal <span class="text-[0.68rem]">({{ cart.count.value }} pcs)</span></dt>
                  <dd class="text-forest">{{ formatIDR(cart.subtotal.value) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4">
                  <dt class="text-muted">{{ isPickup ? 'Pengambilan' : 'Ongkos kirim' }}</dt>
                  <dd class="text-right">
                    <template v-if="isPickup">
                      <span class="inline-block rounded-full border border-olive/35 bg-olive/10 px-2 py-0.5 text-[0.68rem] font-semibold text-olive">Ambil di toko</span>
                    </template>
                    <template v-else-if="hasFreeShipping">
                      <span class="inline-block rounded-full border border-olive/35 bg-olive/10 px-2 py-0.5 text-[0.68rem] font-semibold text-olive">Gratis</span>
                    </template>
                    <template v-else-if="selectedShipping">
                      <span class="font-semibold text-forest">{{ selectedShipping.manual ? 'Konfirmasi admin' : formatIDR(selectedShipping.cost) }}</span>
                      <span class="mt-0.5 block text-[0.68rem] text-muted">
                        {{ selectedShipping.courier_name || selectedShipping.courier.toUpperCase() }} · {{ selectedShipping.service }}
                      </span>
                      <span class="block text-[0.66rem] text-muted/80">{{ selectedShipping.manual ? 'Via WhatsApp' : etdText(selectedShipping.etd) }}</span>
                    </template>
                    <template v-else-if="shippingLoading">
                      <span class="mt-0.5 block h-3.5 w-20 rounded bg-line motion-safe:animate-pulse" />
                    </template>
                    <template v-else-if="manualCity || form.destinationId">
                      <span class="inline-block rounded-full border border-gold/40 bg-gold/[0.09] px-2 py-0.5 text-[0.66rem] font-medium text-forest">Via WhatsApp</span>
                    </template>
                    <template v-else><span class="text-[0.76rem] text-muted">Isi kota tujuan dulu</span></template>
                  </dd>
                </div>
                <div v-if="voucher" class="flex items-start justify-between gap-4">
                  <dt class="text-muted">Diskon <span class="text-[0.68rem]">({{ voucher.code }})</span></dt>
                  <dd class="font-semibold text-olive">−{{ formatIDR(voucher.discount) }}</dd>
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

              <div class="-mx-5 mt-4 flex items-end justify-between gap-4 bg-forest-deep px-5 py-4 text-ivory">
                <span class="text-[0.72rem] leading-snug text-ivory/65">
                  <template v-if="shippingCostDisplay !== null">Total bayar</template>
                  <template v-else>Estimasi total <span class="mt-0.5 block text-[0.64rem] text-ivory/45">belum termasuk ongkir</span></template>
                </span>
                <span class="font-display text-[1.55rem] leading-none tracking-[-0.02em]">{{ formatIDR(grandTotal) }}</span>
              </div>

              <p class="mt-4 flex items-start gap-2 text-[0.7rem] leading-relaxed text-muted">
                <ShieldCheck class="mt-px h-3.5 w-3.5 flex-none text-gold" :stroke-width="1.5" />
                Tanpa pembayaran otomatis. Pesanan dikonfirmasi lewat WhatsApp bersama admin kami.
              </p>
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
  </div>

    <div v-else class="shell py-24">
      <EmptyState title="Belum ada yang bisa dipesan" body="Keranjang Anda kosong. Pilih dulu hadiahnya, lalu kembali ke sini.">
        <AppButton to="/koleksi">Jelajahi koleksi</AppButton>
      </EmptyState>
    </div>
  </div>
</template>
