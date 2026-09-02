<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { nextTick, ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ImagePlus, RotateCcw, RotateCw, ZoomIn, ZoomOut } from 'lucide-vue-next'
import Cropper from 'cropperjs'
import 'cropperjs/dist/cropper.css'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  product: { type: Object, default: null },
  categories: { type: Array, required: true },
  suppliers: { type: Array, default: () => [] },
})

const { push } = useToast()
const existing = props.product
const imagePreview = ref(existing?.image ?? null)

const form = useForm({
  name: existing?.name ?? '',
  slug: existing?.slug ?? '',
  short: existing?.short ?? '',
  description: existing?.description ?? '',
  category_id: existing?.category_id ?? props.categories[0]?.id ?? null,
  supplier_id: existing?.supplier_id ?? '',
  unit: existing?.unit ?? 'pcs',
  price: existing?.price ?? '',
  compare_price: existing?.comparePrice ?? '',
  cost: existing?.cost ?? '',
  stock: existing?.stock ?? 0,
  low_stock_threshold: existing?.lowStock ?? 10,
  storage_location: existing?.storageLocation ?? '',
  sku: existing?.sku ?? '',
  weight: existing?.weight ?? '',
  status: existing?.status ?? 'draft',
  featured: existing?.featured ?? false,
  image: null,
})

const slugify = () => {
  if (!form.slug) form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/webp']
const MAX_BYTES = 4 * 1024 * 1024

const fileInput = ref(null)
const cropperOpen = ref(false)
const cropImgEl = ref(null)
const cropImgUrl = ref(null)
const cropFileMeta = ref(null)
let cropper = null

const resetFileInput = () => { if (fileInput.value) fileInput.value.value = '' }

const closeCropper = () => {
  cropperOpen.value = false
  cropper?.destroy()
  cropper = null
  if (cropImgUrl.value) URL.revokeObjectURL(cropImgUrl.value)
  cropImgUrl.value = null
  cropFileMeta.value = null
  resetFileInput()
}

const onImageChange = async (e) => {
  const file = e.target.files[0]
  if (!file) return

  if (!ALLOWED_TYPES.includes(file.type)) {
    push('Format foto harus JPG, PNG, atau WEBP', { tone: 'danger' })
    resetFileInput()
    return
  }
  if (file.size > MAX_BYTES) {
    push('Ukuran foto maksimal 4 MB', { tone: 'danger' })
    resetFileInput()
    return
  }

  cropImgUrl.value = URL.createObjectURL(file)
  cropFileMeta.value = { name: file.name, type: file.type }
  cropperOpen.value = true
  await nextTick()
  // Full custom: no fixed aspect ratio, crop box freely movable and resizable —
  // admin decides the final shape entirely, not locked to any product-photo convention.
  cropper = new Cropper(cropImgEl.value, {
    viewMode: 1,
    dragMode: 'move',
    autoCropArea: 1,
    cropBoxMovable: true,
    cropBoxResizable: true,
    background: false,
    guides: true,
    zoomOnWheel: true,
    ready: () => {
      const container = cropper.getContainerData()
      const img = cropper.getImageData()
      const cover = Math.max(container.width / img.naturalWidth, container.height / img.naturalHeight)
      cropper.zoomTo(cover)
    },
  })
}

const confirmCrop = () => {
  cropper.getCroppedCanvas({ imageSmoothingQuality: 'high' }).toBlob(
    (blob) => {
      const cropped = new File([blob], cropFileMeta.value.name, { type: cropFileMeta.value.type })
      form.image = cropped
      imagePreview.value = URL.createObjectURL(cropped)
      closeCropper()
    },
    cropFileMeta.value.type,
    0.92,
  )
}

const save = () => {
  const onSuccess = () => push(existing ? 'Perubahan produk disimpan' : 'Produk baru dibuat', { tone: 'success' })
  if (existing) {
    form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/produk/${existing.slug}`, {
      preserveScroll: true,
      onSuccess,
    })
  } else {
    form.post('/admin/produk', { onSuccess })
  }
}
</script>

<template>
  <div>
    <Link href="/admin/produk" class="inline-flex items-center gap-2 text-[0.8rem] text-muted transition hover:text-forest">
      <ArrowLeft class="h-3.5 w-3.5" /> Semua produk
    </Link>

    <header class="mt-5 flex flex-wrap items-end justify-between gap-4">
      <h1 class="text-[2.1rem] leading-none">{{ existing ? existing.name : 'Produk baru' }}</h1>
      <div class="flex gap-3">
        <AppButton size="sm" :loading="form.processing" @click="save">Simpan produk</AppButton>
      </div>
    </header>

    <div v-if="Object.keys(form.errors).length" class="mt-6 border border-danger/40 bg-danger/10 px-5 py-4 text-[0.83rem] text-danger">
      <p class="font-semibold">Simpan gagal — periksa kembali isian di bawah.</p>
      <ul class="mt-2 list-disc space-y-1 pl-5">
        <li v-for="(msg, key) in form.errors" :key="key">{{ Array.isArray(msg) ? msg[0] : msg }}</li>
      </ul>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <div class="space-y-6">
        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Informasi dasar</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="f-name">Nama produk</label>
              <input id="f-name" v-model="form.name" class="field" placeholder="Contoh: Arafah Premium Box" @blur="slugify" />
              <p v-if="form.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.name }}</p>
            </div>
            <div>
              <label class="field-label" for="f-slug">Slug URL</label>
              <div class="flex items-center gap-2">
                <span class="text-[0.8rem] text-muted">arafahgift.id/produk/</span>
                <input id="f-slug" v-model="form.slug" class="field" placeholder="arafah-premium-box" />
              </div>
              <p v-if="form.errors.slug" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.slug }}</p>
            </div>
            <div>
              <label class="field-label" for="f-short">Deskripsi singkat</label>
              <input id="f-short" v-model="form.short" class="field" placeholder="Satu kalimat yang muncul di kartu produk" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label" for="f-unit">Satuan</label>
                <input id="f-unit" v-model="form.unit" class="field" list="unit-presets" placeholder="pcs / box / kg / liter / set" />
                <datalist id="unit-presets">
                  <option value="pcs" /><option value="box" /><option value="kg" />
                  <option value="liter" /><option value="set" /><option value="pak" />
                </datalist>
                <p v-if="form.errors.unit" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.unit }}</p>
              </div>
              <div>
                <label class="field-label" for="f-supplier">Supplier</label>
                <select id="f-supplier" v-model="form.supplier_id" class="field">
                  <option value="">— tanpa supplier —</option>
                  <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                </select>
                <p v-if="form.errors.supplier_id" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.supplier_id }}</p>
              </div>
            </div>
            <div>
              <label class="field-label" for="f-desc">Deskripsi lengkap</label>
              <textarea id="f-desc" v-model="form.description" rows="6" class="field" />
            </div>
          </div>
        </section>

        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Foto produk</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Bentuk dan rasio bebas — atur sendiri di editor. JPG/PNG/WEBP, maks 4 MB.</p>
          <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="arch aspect-[4/5] overflow-hidden border border-line bg-ivory">
              <img v-if="imagePreview" :src="imagePreview" alt="Foto produk" class="h-full w-full object-cover" />
              <ProductArt v-else :art="existing?.art ?? 'giftset'" :tone="1" />
            </div>
            <label class="arch flex aspect-[4/5] cursor-pointer flex-col items-center justify-center gap-2 border border-dashed border-line bg-ivory/60 text-muted transition hover:border-olive/60 hover:text-forest">
              <ImagePlus class="h-5 w-5 text-gold" :stroke-width="1.4" />
              <span class="text-[0.75rem]">Unggah</span>
              <input ref="fileInput" type="file" accept="image/*" class="sr-only" @change="onImageChange" />
            </label>
          </div>
          <p v-if="form.errors.image" class="mt-2 text-[0.72rem] text-danger">{{ form.errors.image }}</p>
        </section>

        <div v-if="cropperOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-forest/50 p-4" @click.self="closeCropper">
          <div class="w-full max-w-lg border border-line bg-surface p-5">
            <h3 class="font-display text-xl">Sesuaikan foto</h3>
            <p class="mt-1 text-[0.75rem] text-muted">Geser, putar, perbesar, atau ubah bentuk kotak crop sesuka Anda — tarik sudut/tepi kotak untuk mengubah bentuknya.</p>
            <div class="mt-4 h-[420px] w-full overflow-hidden bg-ivory">
              <img ref="cropImgEl" :src="cropImgUrl" alt="" class="block max-w-full" />
            </div>
            <div class="mt-3 flex items-center justify-center gap-2">
              <button type="button" class="border border-line p-2 text-muted transition hover:border-olive/60 hover:text-forest" title="Putar kiri" @click="cropper.rotate(-90)">
                <RotateCcw class="h-4 w-4" />
              </button>
              <button type="button" class="border border-line p-2 text-muted transition hover:border-olive/60 hover:text-forest" title="Putar kanan" @click="cropper.rotate(90)">
                <RotateCw class="h-4 w-4" />
              </button>
              <button type="button" class="border border-line p-2 text-muted transition hover:border-olive/60 hover:text-forest" title="Perkecil" @click="cropper.zoom(-0.1)">
                <ZoomOut class="h-4 w-4" />
              </button>
              <button type="button" class="border border-line p-2 text-muted transition hover:border-olive/60 hover:text-forest" title="Perbesar" @click="cropper.zoom(0.1)">
                <ZoomIn class="h-4 w-4" />
              </button>
              <button type="button" class="border border-line px-3 py-2 text-[0.75rem] text-muted transition hover:border-olive/60 hover:text-forest" @click="cropper.reset()">
                Reset
              </button>
            </div>
            <div class="mt-4 flex justify-end gap-3">
              <button type="button" class="text-[0.8rem] text-muted transition hover:text-forest" @click="closeCropper">Batal</button>
              <AppButton size="sm" @click="confirmCrop">Gunakan foto</AppButton>
            </div>
          </div>
        </div>

        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Harga</h2>
          <div class="mt-6 grid gap-5 sm:grid-cols-3">
            <div>
              <label class="field-label" for="f-price">Harga jual</label>
              <input id="f-price" v-model="form.price" inputmode="numeric" class="field" placeholder="649000" />
              <p v-if="form.errors.price" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.price }}</p>
            </div>
            <div>
              <label class="field-label" for="f-compare">Harga coret</label>
              <input id="f-compare" v-model="form.compare_price" inputmode="numeric" class="field" placeholder="749000" />
            </div>
            <div>
              <label class="field-label" for="f-cost">Harga modal</label>
              <input id="f-cost" v-model="form.cost" inputmode="numeric" class="field" placeholder="410000" />
            </div>
          </div>
        </section>
      </div>

      <div class="space-y-6">
        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Status</h2>
          <label class="field-label mt-5" for="f-status">Status publikasi</label>
          <select id="f-status" v-model="form.status" class="field">
            <option value="active">Aktif — tampil di toko</option>
            <option value="draft">Draft — belum tampil</option>
            <option value="archived">Arsip</option>
          </select>
          <label class="mt-5 flex items-center gap-2.5 text-[0.85rem] text-forest">
            <input v-model="form.featured" type="checkbox" class="h-3.5 w-3.5 accent-[rgb(var(--c-forest))]" />
            Tampilkan di section “Favorit dari ArafahGift”
          </label>
        </section>

        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Organisasi</h2>
          <label class="field-label mt-5" for="f-cat">Kategori</label>
          <select id="f-cat" v-model.number="form.category_id" class="field">
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <p v-if="form.errors.category_id" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.category_id }}</p>
        </section>

        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Inventori</h2>
          <div class="mt-5 space-y-5">
            <div>
              <label class="field-label" for="f-sku">SKU</label>
              <input id="f-sku" v-model="form.sku" class="field" placeholder="AGF-BOX-01" />
              <p v-if="form.errors.sku" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.sku }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label" for="f-stock">Stok</label>
                <input id="f-stock" v-model="form.stock" inputmode="numeric" class="field" />
              </div>
              <div>
                <label class="field-label" for="f-low">Batas menipis</label>
                <input id="f-low" v-model="form.low_stock_threshold" inputmode="numeric" class="field" />
              </div>
            </div>
            <div>
              <label class="field-label" for="f-location">Lokasi penyimpanan</label>
              <input id="f-location" v-model="form.storage_location" class="field" placeholder="mis. Rak A-3 / Gudang utama" />
            </div>
            <div>
              <label class="field-label" for="f-weight">Berat kirim (gram)</label>
              <input id="f-weight" v-model="form.weight" inputmode="numeric" class="field" />
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>
