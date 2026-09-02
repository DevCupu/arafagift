<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref, watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { ArrowDown, ArrowUp, ImagePlus, Plus, X } from 'lucide-vue-next'
import BulkActionBar from '@/components/admin/BulkActionBar.vue'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  content: { type: Object, required: true },
  testimonials: { type: Array, required: true },
  faqs: { type: Array, required: true },
  featuredProducts: { type: Array, required: true },
  availableProducts: { type: Array, required: true },
  products: { type: Array, required: true },
})

const { push } = useToast()
const tab = ref('hero')

const tabs = [
  { id: 'hero', label: 'Hero' },
  { id: 'announcement', label: 'Banner' },
  { id: 'featured', label: 'Produk unggulan' },
  { id: 'giftset', label: 'Gift set' },
  { id: 'bulk', label: 'Rombongan' },
  { id: 'story', label: 'Brand story' },
  { id: 'values', label: 'Nilai' },
  { id: 'testimonial', label: 'Testimoni' },
  { id: 'faq', label: 'FAQ' },
  { id: 'instagram', label: 'Instagram' },
]

const artOptions = ['giftset', 'kurma', 'sajadah', 'tasbih', 'madu', 'parfum', 'souvenir']

const form = useForm({
  announcement: props.content.announcement,
  hero: {
    eyebrow: props.content.hero.eyebrow,
    headline: props.content.hero.headline,
    sub: props.content.hero.sub,
    cta: { label: props.content.hero.cta.label, to: props.content.hero.cta.to },
    ctaSecondary: { label: props.content.hero.ctaSecondary?.label ?? 'Hubungi Kami', to: props.content.hero.ctaSecondary?.to ?? '/faq' },
  },
  signature: {
    eyebrow: props.content.signature.eyebrow ?? 'Signature',
    title: props.content.signature.title,
    body: props.content.signature.body,
    productSlug: props.content.signature.productSlug,
    cta: { label: props.content.signature.cta?.label ?? 'Lihat Detail', to: props.content.signature.cta?.to ?? '/koleksi' },
  },
  bulk: {
    eyebrow: props.content.bulk?.eyebrow ?? 'Rombongan',
    title: props.content.bulk?.title ?? 'Souvenir untuk rombongan?',
    sub: props.content.bulk?.sub ?? '',
    points: props.content.bulk?.points?.length ? [...props.content.bulk.points] : ['Mulai 50 pcs'],
    cta: { label: props.content.bulk?.cta?.label ?? 'Konsultasi via WhatsApp', href: props.content.bulk?.cta?.href ?? '' },
  },
  story: {
    eyebrow: props.content.story?.eyebrow ?? 'Cerita kami',
    title: props.content.story?.title ?? '',
    body: props.content.story?.body?.length ? [...props.content.story.body] : [''],
    signature: props.content.story?.signature ?? '',
  },
  instagram: {
    handle: props.content.instagram.handle,
    title: props.content.instagram.title ?? 'Follow the journey',
    url: props.content.instagram.url,
    posts: props.content.instagram.posts?.length ? props.content.instagram.posts.map((p) => ({ ...p })) : [{ art: 'giftset', caption: '' }],
  },
  values: props.content.values?.length ? props.content.values.map((v) => ({ ...v })) : [],
  hero_image: null,
})

const heroImagePreview = ref(props.content.hero.image ?? null)

const onHeroImageChange = (e) => {
  const file = e.target.files[0]
  if (!file) return
  form.hero_image = file
  heroImagePreview.value = URL.createObjectURL(file)
}

const save = () => {
  form
    .transform((data) => ({ ...data, _method: 'put' }))
    .post('/admin/konten', {
      preserveScroll: true,
      onSuccess: () => push('Konten homepage disimpan', { tone: 'success' }),
    })
}

// ── Bulk points management ──
const addBulkPoint = () => { form.bulk.points.push('') }
const removeBulkPoint = (index) => { form.bulk.points.splice(index, 1) }

// ── Story body paragraphs management ──
const addStoryParagraph = () => { form.story.body.push('') }
const removeStoryParagraph = (index) => { form.story.body.splice(index, 1) }

// ── Testimoni ──
const showTestimonialForm = ref(false)
const testimonialForm = useForm({ rating: 5, quote: '', name: '', city: '', context: '' })

const addTestimonial = () => {
  testimonialForm.post('/admin/konten/testimoni', {
    preserveScroll: true,
    onSuccess: () => {
      testimonialForm.reset()
      showTestimonialForm.value = false
      push('Testimoni ditambahkan', { tone: 'success' })
    },
  })
}

const editingTestimonialId = ref(null)
const editTestimonialForm = useForm({ rating: 5, quote: '', name: '', city: '', context: '' })

const startEditTestimonial = (testimonial) => {
  showTestimonialForm.value = false
  editingTestimonialId.value = testimonial.id
  editTestimonialForm.rating = testimonial.rating
  editTestimonialForm.quote = testimonial.quote
  editTestimonialForm.name = testimonial.name
  editTestimonialForm.city = testimonial.city
  editTestimonialForm.context = testimonial.context ?? ''
}

const saveTestimonial = () => {
  editTestimonialForm.put(`/admin/konten/testimoni/${editingTestimonialId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      push('Testimoni diperbarui', { tone: 'success' })
      editingTestimonialId.value = null
    },
  })
}

const deleteTestimonial = (testimonial) => {
  if (!confirm(`Hapus testimoni dari ${testimonial.name}?`)) return
  router.delete(`/admin/konten/testimoni/${testimonial.id}`, {
    preserveScroll: true,
    onSuccess: () => push('Testimoni dihapus', { tone: 'success' }),
  })
}

const selectedTestimonials = ref([])
const bulkDeletingTestimonials = ref(false)
const bulkDeleteTestimonials = () => {
  bulkDeletingTestimonials.value = true
  router.delete('/admin/konten/testimoni/bulk', {
    data: { ids: selectedTestimonials.value },
    preserveScroll: true,
    onSuccess: () => { push(`${selectedTestimonials.value.length} testimoni dihapus`, { tone: 'success' }); selectedTestimonials.value = [] },
    onFinish: () => { bulkDeletingTestimonials.value = false },
  })
}

// ── Produk unggulan ──
const productToAdd = ref(props.availableProducts[0]?.id ?? null)
watch(() => props.availableProducts, (list) => {
  if (!list.some((p) => p.id === productToAdd.value)) productToAdd.value = list[0]?.id ?? null
})

const addFeatured = () => {
  if (!productToAdd.value) return
  router.patch(`/admin/konten/unggulan/${productToAdd.value}/tambah`, {}, {
    preserveScroll: true,
    onSuccess: () => push('Produk ditambahkan ke unggulan', { tone: 'success' }),
  })
}

const removeFeatured = (product) => {
  if (!confirm(`Keluarkan "${product.name}" dari produk unggulan?`)) return
  router.patch(`/admin/konten/unggulan/${product.id}/keluarkan`, {}, {
    preserveScroll: true,
    onSuccess: () => push('Produk dikeluarkan dari unggulan', { tone: 'success' }),
  })
}

const featured = ref([...props.featuredProducts])
watch(() => props.featuredProducts, (list) => { featured.value = [...list] })

const moveFeatured = (index, dir) => {
  const target = index + dir
  if (target < 0 || target >= featured.value.length) return
  const [item] = featured.value.splice(index, 1)
  featured.value.splice(target, 0, item)
  router.patch('/admin/konten/unggulan/reorder', { order: featured.value.map((p) => p.id) }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => push('Urutan produk unggulan disimpan', { tone: 'success' }),
  })
}

// ── Nilai / Value props ──
const iconOptions = ['Sparkles', 'Gift', 'BadgeCheck', 'Send']
const addValue = () => { form.values.push({ icon: 'Sparkles', title: '', body: '' }) }
const removeValue = (index) => { form.values.splice(index, 1) }
const moveValue = (index, dir) => {
  const target = index + dir
  if (target < 0 || target >= form.values.length) return
  const [item] = form.values.splice(index, 1)
  form.values.splice(target, 0, item)
}

// ── Instagram posts ──
const addInstagramPost = () => { form.instagram.posts.push({ art: 'giftset', caption: '' }) }
const removeInstagramPost = (index) => { form.instagram.posts.splice(index, 1) }

// ── FAQ ──
const showFaqForm = ref(false)
const faqForm = useForm({ question: '', answer: '' })

const addFaq = () => {
  faqForm.post('/admin/konten/faq', {
    preserveScroll: true,
    onSuccess: () => {
      faqForm.reset()
      showFaqForm.value = false
      push('FAQ ditambahkan', { tone: 'success' })
    },
  })
}

const editingFaqId = ref(null)
const editFaqForm = useForm({ question: '', answer: '' })

const startEditFaq = (faq) => {
  showFaqForm.value = false
  editingFaqId.value = faq.id
  editFaqForm.question = faq.question
  editFaqForm.answer = faq.answer
}

const saveFaq = () => {
  editFaqForm.put(`/admin/konten/faq/${editingFaqId.value}`, {
    preserveScroll: true,
    onSuccess: () => {
      push('FAQ diperbarui', { tone: 'success' })
      editingFaqId.value = null
    },
  })
}

const deleteFaq = (faq) => {
  if (!confirm(`Hapus pertanyaan "${faq.question}"?`)) return
  router.delete(`/admin/konten/faq/${faq.id}`, {
    preserveScroll: true,
    onSuccess: () => push('FAQ dihapus', { tone: 'success' }),
  })
}

const selectedFaqs = ref([])
const bulkDeletingFaqs = ref(false)
const bulkDeleteFaqs = () => {
  bulkDeletingFaqs.value = true
  router.delete('/admin/konten/faq/bulk', {
    data: { ids: selectedFaqs.value },
    preserveScroll: true,
    onSuccess: () => { push(`${selectedFaqs.value.length} FAQ dihapus`, { tone: 'success' }); selectedFaqs.value = [] },
    onFinish: () => { bulkDeletingFaqs.value = false },
  })
}

const faqs = ref([...props.faqs])
watch(() => props.faqs, (list) => { faqs.value = [...list] })

const moveFaq = (index, dir) => {
  const target = index + dir
  if (target < 0 || target >= faqs.value.length) return
  const [item] = faqs.value.splice(index, 1)
  faqs.value.splice(target, 0, item)
  router.patch('/admin/konten/faq/reorder', { order: faqs.value.map((f) => f.id) }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => push('Urutan FAQ disimpan', { tone: 'success' }),
  })
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Konten homepage</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Semua teks di bawah ini tampil langsung di halaman depan.</p>
      </div>
      <AppButton size="sm" :loading="form.processing" @click="save">Simpan perubahan</AppButton>
    </header>

    <div class="no-scrollbar mt-8 flex gap-6 overflow-x-auto border-b border-line">
      <button
        v-for="t in tabs" :key="t.id" type="button"
        class="relative whitespace-nowrap pb-3 text-[0.83rem] transition"
        :class="tab === t.id ? 'text-forest' : 'text-muted hover:text-forest'"
        @click="tab = t.id"
      >
        {{ t.label }}
        <span v-if="tab === t.id" class="absolute inset-x-0 -bottom-px h-px bg-gold" />
      </button>
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.4fr_1fr]">
      <section class="border border-line bg-surface p-6 sm:p-7">

        <!-- ════════ HERO ════════ -->
        <template v-if="tab === 'hero'">
          <h2 class="font-display text-2xl">Hero</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label">Foto hero</label>
              <div class="mt-2 flex items-center gap-4">
                <div class="arch aspect-[4/5] w-28 flex-none overflow-hidden border border-line bg-ivory">
                  <img v-if="heroImagePreview" :src="heroImagePreview" alt="Foto hero" class="h-full w-full object-cover" />
                </div>
                <label class="inline-flex cursor-pointer items-center gap-2 border border-dashed border-line px-4 py-2.5 text-[0.8rem] text-muted transition hover:border-olive/60 hover:text-forest">
                  <ImagePlus class="h-4 w-4 text-gold" :stroke-width="1.4" />
                  Unggah foto hero
                  <input type="file" accept="image/*" class="sr-only" @change="onHeroImageChange" />
                </label>
              </div>
              <p v-if="form.errors.hero_image" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.hero_image }}</p>
            </div>
            <div>
              <label class="field-label" for="c-eyebrow">Label kecil</label>
              <input id="c-eyebrow" v-model="form.hero.eyebrow" class="field" />
              <p v-if="form.errors['hero.eyebrow']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['hero.eyebrow'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-headline">Headline</label>
              <textarea id="c-headline" v-model="form.hero.headline" rows="2" class="field" />
              <p class="mt-1.5 text-[0.72rem] text-muted">Gunakan enter untuk memecah baris judul.</p>
              <p v-if="form.errors['hero.headline']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['hero.headline'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-sub">Subheadline</label>
              <textarea id="c-sub" v-model="form.hero.sub" rows="3" class="field" />
              <p v-if="form.errors['hero.sub']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['hero.sub'] }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="c-cta">Teks tombol utama</label>
                <input id="c-cta" v-model="form.hero.cta.label" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-ctalink">Tautan tombol utama</label>
                <input id="c-ctalink" v-model="form.hero.cta.to" class="field" />
              </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="c-cta2">Teks tombol kedua</label>
                <input id="c-cta2" v-model="form.hero.ctaSecondary.label" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-cta2link">Tautan tombol kedua</label>
                <input id="c-cta2link" v-model="form.hero.ctaSecondary.to" class="field" />
              </div>
            </div>
          </div>
        </template>

        <!-- ════════ BANNER ════════ -->
        <template v-else-if="tab === 'announcement'">
          <h2 class="font-display text-2xl">Banner pengumuman</h2>
          <label class="field-label mt-6" for="c-ann">Teks banner</label>
          <input id="c-ann" v-model="form.announcement" class="field" />
          <p class="mt-2 text-[0.78rem] text-muted">Tampil di baris paling atas seluruh halaman toko.</p>
          <p v-if="form.errors.announcement" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.announcement }}</p>
        </template>

        <!-- ════════ PRODUK UNGGULAN ════════ -->
        <template v-else-if="tab === 'featured'">
          <h2 class="font-display text-2xl">Produk unggulan</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Unggulan tampil di homepage sesuai urutan di bawah ini. Tandai produk lewat halaman Produk, lalu atur posisinya di sini.</p>
          <ul v-if="featured.length" class="mt-6 divide-y divide-line border-y border-line">
            <li v-for="(p, i) in featured" :key="p.id" class="flex items-center gap-4 py-3.5">
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="p.art" :tone="p.id" /></span>
              <span class="flex-1 min-w-0 text-[0.87rem] text-forest">{{ p.name }}</span>
              <div class="flex items-center gap-2">
                <span class="text-[0.7rem] tabular-nums text-muted">{{ i + 1 }} / {{ featured.length }}</span>
                <button type="button" class="text-muted transition hover:text-forest disabled:opacity-30" :disabled="i === 0" title="Naikkan posisi" @click="moveFeatured(i, -1)"><ArrowUp class="h-4 w-4" /></button>
                <button type="button" class="text-muted transition hover:text-forest disabled:opacity-30" :disabled="i === featured.length - 1" title="Turunkan posisi" @click="moveFeatured(i, 1)"><ArrowDown class="h-4 w-4" /></button>
                <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="removeFeatured(p)">Keluarkan</button>
              </div>
            </li>
          </ul>
          <p v-else class="mt-6 text-[0.83rem] text-muted">Belum ada produk yang ditandai unggulan.</p>

          <div v-if="availableProducts.length" class="mt-6 flex flex-wrap items-end gap-3">
            <div class="flex-1">
              <label class="field-label" for="c-addfeatured">Tambah produk unggulan</label>
              <select id="c-addfeatured" v-model.number="productToAdd" class="field">
                <option v-for="p in availableProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>
            <AppButton size="sm" variant="quiet" type="button" @click="addFeatured">Tambahkan</AppButton>
          </div>
        </template>

        <!-- ════════ GIFT SET ════════ -->
        <template v-else-if="tab === 'giftset'">
          <h2 class="font-display text-2xl">Section gift set</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-sigeyebrow">Label kecil</label>
              <input id="c-sigeyebrow" v-model="form.signature.eyebrow" class="field" />
              <p v-if="form.errors['signature.eyebrow']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['signature.eyebrow'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-sigtitle">Judul</label>
              <input id="c-sigtitle" v-model="form.signature.title" class="field" />
              <p v-if="form.errors['signature.title']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['signature.title'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-sigbody">Deskripsi</label>
              <textarea id="c-sigbody" v-model="form.signature.body" rows="3" class="field" />
              <p v-if="form.errors['signature.body']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['signature.body'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-sigprod">Produk yang ditampilkan</label>
              <select id="c-sigprod" v-model="form.signature.productSlug" class="field">
                <option v-for="p in products" :key="p.id" :value="p.slug">{{ p.name }}</option>
              </select>
              <p v-if="form.errors['signature.productSlug']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['signature.productSlug'] }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="c-sigcta">Teks tombol</label>
                <input id="c-sigcta" v-model="form.signature.cta.label" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-sigctalink">Tautan tombol</label>
                <input id="c-sigctalink" v-model="form.signature.cta.to" class="field" />
              </div>
            </div>
          </div>
        </template>

        <!-- ════════ ROMBONGAN ════════ -->
        <template v-else-if="tab === 'bulk'">
          <h2 class="font-display text-2xl">Section rombongan</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Tampil di bagian "Souvenir untuk satu rombongan?"</p>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-bulkeyebrow">Label kecil</label>
              <input id="c-bulkeyebrow" v-model="form.bulk.eyebrow" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-bulktitle">Judul</label>
              <input id="c-bulktitle" v-model="form.bulk.title" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-bulksub">Deskripsi</label>
              <textarea id="c-bulksub" v-model="form.bulk.sub" rows="3" class="field" />
            </div>
            <div>
              <label class="field-label">Poin-poin</label>
              <div class="mt-2 space-y-2">
                <div v-for="(point, i) in form.bulk.points" :key="i" class="flex items-center gap-2">
                  <input v-model="form.bulk.points[i]" class="field flex-1" placeholder="Poin ke-1" />
                  <button v-if="form.bulk.points.length > 1" type="button" class="text-muted hover:text-danger" @click="removeBulkPoint(i)">
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
              <button type="button" class="mt-2 flex items-center gap-1.5 text-[0.78rem] text-forest transition hover:text-forest-deep" @click="addBulkPoint">
                <Plus class="h-3.5 w-3.5" /> Tambah poin
              </button>
              <p v-if="form.errors['bulk.points']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['bulk.points'] }}</p>
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="c-bulkcta">Teks tombol</label>
                <input id="c-bulkcta" v-model="form.bulk.cta.label" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-bulkctahref">Tautan tombol (URL WA)</label>
                <input id="c-bulkctahref" v-model="form.bulk.cta.href" class="field" placeholder="https://wa.me/6281234567890" />
              </div>
            </div>
          </div>
        </template>

        <!-- ════════ BRAND STORY ════════ -->
        <template v-else-if="tab === 'story'">
          <h2 class="font-display text-2xl">Brand story</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Section "Setiap perjalanan pulang membawa cerita."</p>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-storyeyebrow">Label kecil</label>
              <input id="c-storyeyebrow" v-model="form.story.eyebrow" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-storytitle">Judul</label>
              <input id="c-storytitle" v-model="form.story.title" class="field" />
            </div>
            <div>
              <label class="field-label">Paragraf</label>
              <div class="mt-2 space-y-2">
                <div v-for="(para, i) in form.story.body" :key="i" class="flex items-start gap-2">
                  <textarea :value="form.story.body[i]" @input="form.story.body[i] = $event.target.value" rows="3" class="field flex-1" :placeholder="`Paragraf ${i + 1}`" />
                  <button v-if="form.story.body.length > 1" type="button" class="mt-2 text-muted hover:text-danger" @click="removeStoryParagraph(i)">
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
              <button type="button" class="mt-2 flex items-center gap-1.5 text-[0.78rem] text-forest transition hover:text-forest-deep" @click="addStoryParagraph">
                <Plus class="h-3.5 w-3.5" /> Tambah paragraf
              </button>
              <p v-if="form.errors['story.body']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['story.body'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-storysig">Tanda tangan</label>
              <input id="c-storysig" v-model="form.story.signature" class="field" placeholder="Tim ArafahGift, Jakarta" />
            </div>
          </div>
        </template>

        <!-- ════════ NILAI / VALUE PROPS ════════ -->
        <template v-else-if="tab === 'values'">
          <h2 class="font-display text-2xl">Nilai</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Section "Kenapa ArafahGift". Maksimal 8 kartu, masing-masing dengan ikon, judul, dan deskripsi.</p>
          <div class="mt-6 space-y-5">
            <div v-for="(value, i) in form.values" :key="i" class="border border-line p-5">
              <div class="flex items-center justify-between gap-3">
                <p class="text-[0.78rem] uppercase tracking-[0.12em] text-muted">Kartu {{ i + 1 }}</p>
                <div class="flex items-center gap-2">
                  <button type="button" class="text-muted hover:text-forest disabled:opacity-30" :disabled="i === 0" title="Naikkan" @click="moveValue(i, -1)"><ArrowUp class="h-4 w-4" /></button>
                  <button type="button" class="text-muted hover:text-forest disabled:opacity-30" :disabled="i === form.values.length - 1" title="Turunkan" @click="moveValue(i, 1)"><ArrowDown class="h-4 w-4" /></button>
                  <button v-if="form.values.length > 1" type="button" class="text-muted hover:text-danger" @click="removeValue(i)"><X class="h-4 w-4" /></button>
                </div>
              </div>
              <div class="mt-4 space-y-4">
                <div>
                  <label class="field-label" :for="`v-icon-${i}`">Ikon</label>
                  <select :id="`v-icon-${i}`" v-model="form.values[i].icon" class="field">
                    <option v-for="ic in iconOptions" :key="ic" :value="ic">{{ ic }}</option>
                  </select>
                </div>
                <div>
                  <label class="field-label" :for="`v-title-${i}`">Judul</label>
                  <input :id="`v-title-${i}`" v-model="form.values[i].title" class="field" />
                </div>
                <div>
                  <label class="field-label" :for="`v-body-${i}`">Deskripsi</label>
                  <textarea :id="`v-body-${i}`" v-model="form.values[i].body" rows="3" class="field" />
                </div>
              </div>
            </div>
            <p v-if="form.errors['values']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['values'] }}</p>
            <button type="button" class="flex items-center gap-1.5 text-[0.78rem] text-forest transition hover:text-forest-deep" @click="addValue">
              <Plus class="h-3.5 w-3.5" /> Tambah kartu
            </button>
          </div>
        </template>

        <!-- ════════ TESTIMONI ════════ -->
        <template v-else-if="tab === 'testimonial'">
          <h2 class="font-display text-2xl">Testimoni</h2>
          <BulkActionBar
            :count="selectedTestimonials.length" label="testimoni" class="mt-6" :loading="bulkDeletingTestimonials"
            @clear="selectedTestimonials = []" @delete="bulkDeleteTestimonials"
          />
          <ul class="mt-6 space-y-4">
            <li v-for="t in testimonials" :key="t.id" class="flex gap-3 border border-line p-5">
              <label class="flex-none pt-1">
                <span class="sr-only">Pilih testimoni {{ t.name }}</span>
                <input type="checkbox" class="h-3.5 w-3.5 accent-olive" :value="t.id" v-model="selectedTestimonials" />
              </label>
              <div class="flex-1">
              <template v-if="editingTestimonialId === t.id">
                <div class="space-y-4">
                  <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                      <label class="field-label" :for="`t-edit-name-${t.id}`">Nama</label>
                      <input :id="`t-edit-name-${t.id}`" v-model="editTestimonialForm.name" class="field" required />
                      <p v-if="editTestimonialForm.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ editTestimonialForm.errors.name }}</p>
                    </div>
                    <div>
                      <label class="field-label" :for="`t-edit-city-${t.id}`">Kota</label>
                      <input :id="`t-edit-city-${t.id}`" v-model="editTestimonialForm.city" class="field" required />
                    </div>
                  </div>
                  <div>
                    <label class="field-label" :for="`t-edit-quote-${t.id}`">Testimoni</label>
                    <textarea :id="`t-edit-quote-${t.id}`" v-model="editTestimonialForm.quote" rows="3" class="field" required />
                    <p v-if="editTestimonialForm.errors.quote" class="mt-1.5 text-[0.72rem] text-danger">{{ editTestimonialForm.errors.quote }}</p>
                  </div>
                  <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                      <label class="field-label" :for="`t-edit-context-${t.id}`">Konteks (opsional)</label>
                      <input :id="`t-edit-context-${t.id}`" v-model="editTestimonialForm.context" class="field" placeholder="Nama produk / pesanan" />
                    </div>
                    <div>
                      <label class="field-label" :for="`t-edit-rating-${t.id}`">Rating</label>
                      <select :id="`t-edit-rating-${t.id}`" v-model.number="editTestimonialForm.rating" class="field">
                        <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} bintang</option>
                      </select>
                    </div>
                  </div>
                  <div class="flex gap-3">
                    <AppButton size="sm" type="button" :loading="editTestimonialForm.processing" @click="saveTestimonial">Simpan</AppButton>
                    <AppButton size="sm" variant="quiet" type="button" @click="editingTestimonialId = null">Batal</AppButton>
                  </div>
                </div>
              </template>
              <template v-else>
                <p class="font-display text-[1.1rem] italic leading-snug text-forest">"{{ t.quote }}"</p>
                <div class="mt-3 flex items-center justify-between gap-4">
                  <p class="text-[0.78rem] text-muted">{{ t.name }} · {{ t.city }} · {{ t.rating }} bintang</p>
                  <div class="flex gap-4">
                    <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-forest" @click="startEditTestimonial(t)">Edit</button>
                    <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="deleteTestimonial(t)">Hapus</button>
                  </div>
                </div>
              </template>
              </div>
            </li>
          </ul>

          <form v-if="showTestimonialForm" class="mt-6 space-y-4 border border-line p-5" @submit.prevent="addTestimonial">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="field-label" for="t-name">Nama</label>
                <input id="t-name" v-model="testimonialForm.name" class="field" required />
                <p v-if="testimonialForm.errors.name" class="mt-1.5 text-[0.72rem] text-danger">{{ testimonialForm.errors.name }}</p>
              </div>
              <div>
                <label class="field-label" for="t-city">Kota</label>
                <input id="t-city" v-model="testimonialForm.city" class="field" required />
              </div>
            </div>
            <div>
              <label class="field-label" for="t-quote">Testimoni</label>
              <textarea id="t-quote" v-model="testimonialForm.quote" rows="3" class="field" required />
              <p v-if="testimonialForm.errors.quote" class="mt-1.5 text-[0.72rem] text-danger">{{ testimonialForm.errors.quote }}</p>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <label class="field-label" for="t-context">Konteks (opsional)</label>
                <input id="t-context" v-model="testimonialForm.context" class="field" placeholder="Nama produk / pesanan" />
              </div>
              <div>
                <label class="field-label" for="t-rating">Rating</label>
                <select id="t-rating" v-model.number="testimonialForm.rating" class="field">
                  <option v-for="n in [5, 4, 3, 2, 1]" :key="n" :value="n">{{ n }} bintang</option>
                </select>
              </div>
            </div>
            <div class="flex gap-3">
              <AppButton size="sm" type="submit" :loading="testimonialForm.processing">Simpan testimoni</AppButton>
              <AppButton size="sm" variant="quiet" type="button" @click="showTestimonialForm = false">Batal</AppButton>
            </div>
          </form>
          <AppButton v-else variant="quiet" size="sm" class="mt-5" @click="showTestimonialForm = true">Tambah testimoni</AppButton>
        </template>

        <!-- ════════ FAQ ════════ -->
        <template v-else-if="tab === 'faq'">
          <h2 class="font-display text-2xl">FAQ</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Tampil di homepage dan halaman /faq.</p>
          <BulkActionBar
            :count="selectedFaqs.length" label="FAQ" class="mt-6" :loading="bulkDeletingFaqs"
            @clear="selectedFaqs = []" @delete="bulkDeleteFaqs"
          />
          <ul class="mt-6 space-y-4">
            <li v-for="(f, i) in faqs" :key="f.id" class="flex gap-3 border border-line p-5">
              <label class="flex-none pt-1">
                <span class="sr-only">Pilih FAQ {{ f.question }}</span>
                <input type="checkbox" class="h-3.5 w-3.5 accent-olive" :value="f.id" v-model="selectedFaqs" />
              </label>
              <div class="flex-1">
              <template v-if="editingFaqId === f.id">
                <div class="space-y-4">
                  <div>
                    <label class="field-label" :for="`f-edit-q-${f.id}`">Pertanyaan</label>
                    <input :id="`f-edit-q-${f.id}`" v-model="editFaqForm.question" class="field" required />
                    <p v-if="editFaqForm.errors.question" class="mt-1.5 text-[0.72rem] text-danger">{{ editFaqForm.errors.question }}</p>
                  </div>
                  <div>
                    <label class="field-label" :for="`f-edit-a-${f.id}`">Jawaban</label>
                    <textarea :id="`f-edit-a-${f.id}`" v-model="editFaqForm.answer" rows="3" class="field" required />
                    <p v-if="editFaqForm.errors.answer" class="mt-1.5 text-[0.72rem] text-danger">{{ editFaqForm.errors.answer }}</p>
                  </div>
                  <div class="flex gap-3">
                    <AppButton size="sm" type="button" :loading="editFaqForm.processing" @click="saveFaq">Simpan</AppButton>
                    <AppButton size="sm" variant="quiet" type="button" @click="editingFaqId = null">Batal</AppButton>
                  </div>
                </div>
              </template>
              <template v-else>
                <div class="flex items-start justify-between gap-4">
                  <p class="font-display text-[1.05rem] leading-snug text-forest">{{ f.question }}</p>
                  <div class="flex flex-none items-center gap-2">
                    <button type="button" class="text-muted transition hover:text-forest disabled:opacity-30" :disabled="i === 0" title="Naikkan posisi" @click="moveFaq(i, -1)"><ArrowUp class="h-4 w-4" /></button>
                    <button type="button" class="text-muted transition hover:text-forest disabled:opacity-30" :disabled="i === faqs.length - 1" title="Turunkan posisi" @click="moveFaq(i, 1)"><ArrowDown class="h-4 w-4" /></button>
                  </div>
                </div>
                <p class="mt-2 text-[0.85rem] leading-relaxed text-muted">{{ f.answer }}</p>
                <div class="mt-3 flex gap-4">
                  <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-forest" @click="startEditFaq(f)">Edit</button>
                  <button type="button" class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="deleteFaq(f)">Hapus</button>
                </div>
              </template>
              </div>
            </li>
          </ul>

          <form v-if="showFaqForm" class="mt-6 space-y-4 border border-line p-5" @submit.prevent="addFaq">
            <div>
              <label class="field-label" for="f-q">Pertanyaan</label>
              <input id="f-q" v-model="faqForm.question" class="field" required />
              <p v-if="faqForm.errors.question" class="mt-1.5 text-[0.72rem] text-danger">{{ faqForm.errors.question }}</p>
            </div>
            <div>
              <label class="field-label" for="f-a">Jawaban</label>
              <textarea id="f-a" v-model="faqForm.answer" rows="3" class="field" required />
              <p v-if="faqForm.errors.answer" class="mt-1.5 text-[0.72rem] text-danger">{{ faqForm.errors.answer }}</p>
            </div>
            <div class="flex gap-3">
              <AppButton size="sm" type="submit" :loading="faqForm.processing">Simpan FAQ</AppButton>
              <AppButton size="sm" variant="quiet" type="button" @click="showFaqForm = false">Batal</AppButton>
            </div>
          </form>
          <AppButton v-else variant="quiet" size="sm" class="mt-5" @click="showFaqForm = true">Tambah FAQ</AppButton>
        </template>

        <!-- ════════ INSTAGRAM ════════ -->
        <template v-else-if="tab === 'instagram'">
          <h2 class="font-display text-2xl">Section Instagram</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-ig">Username</label>
              <input id="c-ig" v-model="form.instagram.handle" class="field" />
              <p v-if="form.errors['instagram.handle']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['instagram.handle'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-igtitle">Judul section</label>
              <input id="c-igtitle" v-model="form.instagram.title" class="field" />
              <p v-if="form.errors['instagram.title']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['instagram.title'] }}</p>
            </div>
            <div>
              <label class="field-label" for="c-igurl">Tautan profil</label>
              <input id="c-igurl" v-model="form.instagram.url" class="field" />
              <p v-if="form.errors['instagram.url']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['instagram.url'] }}</p>
            </div>
            <div>
              <label class="field-label">Foto grid (maks. 12)</label>
              <p class="mt-1 text-[0.78rem] text-muted">Belum ada foto asli — pilih motif placeholder yang paling cocok untuk tiap kotak.</p>
              <div class="mt-3 space-y-3">
                <div v-for="(post, i) in form.instagram.posts" :key="i" class="flex items-start gap-2 border border-line p-4">
                  <span class="arch h-14 w-14 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="post.art" :tone="i" /></span>
                  <div class="flex-1 space-y-2">
                    <select v-model="form.instagram.posts[i].art" class="field">
                      <option v-for="a in artOptions" :key="a" :value="a">{{ a }}</option>
                    </select>
                    <input v-model="form.instagram.posts[i].caption" class="field" placeholder="Caption" />
                  </div>
                  <button v-if="form.instagram.posts.length > 1" type="button" class="mt-2 text-muted hover:text-danger" @click="removeInstagramPost(i)">
                    <X class="h-4 w-4" />
                  </button>
                </div>
              </div>
              <button type="button" class="mt-2 flex items-center gap-1.5 text-[0.78rem] text-forest transition hover:text-forest-deep" @click="addInstagramPost">
                <Plus class="h-3.5 w-3.5" /> Tambah foto
              </button>
              <p v-if="form.errors['instagram.posts']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['instagram.posts'] }}</p>
            </div>
          </div>
        </template>
      </section>

      <!-- ════════ PRATINJAU ════════ -->
      <aside class="border border-line bg-ivory p-6">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Pratinjau</p>
        <div class="mt-5 border border-line bg-surface p-6">
          <p class="eyebrow">{{ tab === 'giftset' ? form.signature.eyebrow : tab === 'bulk' ? form.bulk.eyebrow : tab === 'story' ? form.story.eyebrow : form.hero.eyebrow }}</p>
          <p class="mt-4 whitespace-pre-line font-display text-[1.9rem] leading-[1.06] text-forest">
            {{ tab === 'giftset' ? form.signature.title : tab === 'bulk' ? form.bulk.title : tab === 'story' ? form.story.title : form.hero.headline }}
          </p>
          <p class="mt-4 text-[0.85rem] leading-relaxed text-muted">
            {{ tab === 'giftset' ? form.signature.body : tab === 'bulk' ? form.bulk.sub : tab === 'story' ? form.story.body[0] : form.hero.sub }}
          </p>
          <span v-if="tab === 'hero' || tab === 'giftset' || tab === 'bulk'" class="mt-6 inline-block border border-forest bg-forest px-5 py-2.5 text-[0.8rem] text-ivory">
            {{ tab === 'giftset' ? form.signature.cta.label : tab === 'bulk' ? form.bulk.cta.label : form.hero.cta.label }}
          </span>
        </div>
        <div class="mt-4 border border-line bg-forest-deep px-4 py-2.5 text-center text-[0.7rem] uppercase tracking-[0.12em] text-ivory/85">
          {{ form.announcement }}
        </div>
      </aside>
    </div>
  </div>
</template>
