<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { GripVertical } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { useToast } from '@/composables/useToast'

const props = defineProps({
  content: { type: Object, required: true },
  testimonials: { type: Array, required: true },
  featuredProducts: { type: Array, required: true },
})

const { push } = useToast()
const tab = ref('hero')

const tabs = [
  { id: 'hero', label: 'Hero' },
  { id: 'announcement', label: 'Banner' },
  { id: 'featured', label: 'Produk unggulan' },
  { id: 'giftset', label: 'Gift set' },
  { id: 'testimonial', label: 'Testimoni' },
  { id: 'instagram', label: 'Instagram' },
]

const form = useForm({
  announcement: props.content.announcement,
  hero: {
    eyebrow: props.content.hero.eyebrow,
    headline: props.content.hero.headline,
    sub: props.content.hero.sub,
    cta: { label: props.content.hero.cta.label, to: props.content.hero.cta.to },
  },
  signature: {
    title: props.content.signature.title,
    body: props.content.signature.body,
    productSlug: props.content.signature.productSlug,
  },
  instagram: {
    handle: props.content.instagram.handle,
    url: props.content.instagram.url,
  },
})

const save = () => {
  form.put('/admin/konten', {
    preserveScroll: true,
    onSuccess: () => push('Konten homepage disimpan', { tone: 'success' }),
  })
}

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

const deleteTestimonial = (testimonial) => {
  if (!confirm(`Hapus testimoni dari ${testimonial.name}?`)) return
  router.delete(`/admin/konten/testimoni/${testimonial.id}`, {
    preserveScroll: true,
    onSuccess: () => push('Testimoni dihapus', { tone: 'success' }),
  })
}

// ── Produk unggulan ──
const removeFeatured = (product) => {
  if (!confirm(`Keluarkan "${product.name}" dari produk unggulan?`)) return
  router.patch(`/admin/konten/unggulan/${product.id}`, {}, {
    preserveScroll: true,
    onSuccess: () => push('Produk dikeluarkan dari unggulan', { tone: 'success' }),
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
        v-for="t in tabs" :key="t.id"
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
        <template v-if="tab === 'hero'">
          <h2 class="font-display text-2xl">Hero</h2>
          <div class="mt-6 space-y-5">
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
                <label class="field-label" for="c-cta">Teks tombol</label>
                <input id="c-cta" v-model="form.hero.cta.label" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-ctalink">Tautan tombol</label>
                <input id="c-ctalink" v-model="form.hero.cta.to" class="field" />
              </div>
            </div>
          </div>
        </template>

        <template v-else-if="tab === 'announcement'">
          <h2 class="font-display text-2xl">Banner pengumuman</h2>
          <label class="field-label mt-6" for="c-ann">Teks banner</label>
          <input id="c-ann" v-model="form.announcement" class="field" />
          <p class="mt-2 text-[0.78rem] text-muted">Tampil di baris paling atas seluruh halaman toko.</p>
          <p v-if="form.errors.announcement" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors.announcement }}</p>
        </template>

        <template v-else-if="tab === 'featured'">
          <h2 class="font-display text-2xl">Produk unggulan</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Empat produk teratas tampil di section “Favorit dari ArafahGift”. Tandai produk sebagai unggulan lewat halaman Produk.</p>
          <ul v-if="featuredProducts.length" class="mt-6 divide-y divide-line border-y border-line">
            <li v-for="p in featuredProducts" :key="p.id" class="flex items-center gap-4 py-3.5">
              <GripVertical class="h-4 w-4 flex-none cursor-grab text-muted" />
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="p.art" :tone="p.id" /></span>
              <span class="flex-1 text-[0.87rem] text-forest">{{ p.name }}</span>
              <button class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="removeFeatured(p)">Keluarkan</button>
            </li>
          </ul>
          <p v-else class="mt-6 text-[0.83rem] text-muted">Belum ada produk yang ditandai unggulan.</p>
        </template>

        <template v-else-if="tab === 'giftset'">
          <h2 class="font-display text-2xl">Section gift set</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-sigtitle">Judul</label>
              <input id="c-sigtitle" v-model="form.signature.title" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-sigbody">Deskripsi</label>
              <textarea id="c-sigbody" v-model="form.signature.body" rows="3" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-sigprod">Produk yang ditampilkan</label>
              <select id="c-sigprod" v-model="form.signature.productSlug" class="field">
                <option v-for="p in featuredProducts" :key="p.id" :value="p.slug">{{ p.name }}</option>
              </select>
            </div>
          </div>
        </template>

        <template v-else-if="tab === 'testimonial'">
          <h2 class="font-display text-2xl">Testimoni</h2>
          <ul class="mt-6 space-y-4">
            <li v-for="t in testimonials" :key="t.id" class="border border-line p-5">
              <p class="font-display text-[1.1rem] italic leading-snug text-forest">“{{ t.quote }}”</p>
              <div class="mt-3 flex items-center justify-between gap-4">
                <p class="text-[0.78rem] text-muted">{{ t.name }} · {{ t.city }} · {{ t.rating }} bintang</p>
                <button class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger" @click="deleteTestimonial(t)">Hapus</button>
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

        <template v-else>
          <h2 class="font-display text-2xl">Section Instagram</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-ig">Username</label>
              <input id="c-ig" v-model="form.instagram.handle" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-igurl">Tautan profil</label>
              <input id="c-igurl" v-model="form.instagram.url" class="field" />
              <p v-if="form.errors['instagram.url']" class="mt-1.5 text-[0.72rem] text-danger">{{ form.errors['instagram.url'] }}</p>
            </div>
          </div>
        </template>
      </section>

      <aside class="border border-line bg-ivory p-6">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Pratinjau</p>
        <div class="mt-5 border border-line bg-surface p-6">
          <p class="eyebrow">{{ form.hero.eyebrow }}</p>
          <p class="mt-4 whitespace-pre-line font-display text-[1.9rem] leading-[1.06] text-forest">{{ form.hero.headline }}</p>
          <p class="mt-4 text-[0.85rem] leading-relaxed text-muted">{{ form.hero.sub }}</p>
          <span class="mt-6 inline-block border border-forest bg-forest px-5 py-2.5 text-[0.8rem] text-ivory">{{ form.hero.cta.label }}</span>
        </div>
        <div class="mt-4 border border-line bg-forest-deep px-4 py-2.5 text-center text-[0.7rem] uppercase tracking-[0.12em] text-ivory/85">
          {{ form.announcement }}
        </div>
      </aside>
    </div>
  </div>
</template>
