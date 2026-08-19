<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { reactive, ref } from 'vue'
import { GripVertical } from 'lucide-vue-next'
import AppButton from '@/components/ui/AppButton.vue'
import ProductArt from '@/components/art/ProductArt.vue'
import { homeContent, testimonials } from '@/data/content'
import { featuredProducts } from '@/data/catalog'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const saving = ref(false)
const tab = ref('hero')

const tabs = [
  { id: 'hero', label: 'Hero' },
  { id: 'announcement', label: 'Banner' },
  { id: 'featured', label: 'Produk unggulan' },
  { id: 'giftset', label: 'Gift set' },
  { id: 'testimonial', label: 'Testimoni' },
  { id: 'instagram', label: 'Instagram' },
]

const form = reactive({
  announcement: homeContent.announcement,
  heroEyebrow: homeContent.hero.eyebrow,
  heroHeadline: homeContent.hero.headline,
  heroSub: homeContent.hero.sub,
  heroCta: homeContent.hero.cta.label,
  heroCtaLink: homeContent.hero.cta.to,
  sigTitle: homeContent.signature.title,
  sigBody: homeContent.signature.body,
  sigProduct: homeContent.signature.productSlug,
  igHandle: homeContent.instagram.handle,
  igUrl: homeContent.instagram.url,
})

const save = () => {
  saving.value = true
  setTimeout(() => { saving.value = false; push('Konten homepage disimpan', { tone: 'success' }) }, 800)
}
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Konten homepage</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Semua teks di bawah ini tampil langsung di halaman depan.</p>
      </div>
      <AppButton size="sm" :loading="saving" @click="save">Simpan perubahan</AppButton>
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
              <input id="c-eyebrow" v-model="form.heroEyebrow" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-headline">Headline</label>
              <textarea id="c-headline" v-model="form.heroHeadline" rows="2" class="field" />
              <p class="mt-1.5 text-[0.72rem] text-muted">Gunakan enter untuk memecah baris judul.</p>
            </div>
            <div>
              <label class="field-label" for="c-sub">Subheadline</label>
              <textarea id="c-sub" v-model="form.heroSub" rows="3" class="field" />
            </div>
            <div class="grid gap-5 sm:grid-cols-2">
              <div>
                <label class="field-label" for="c-cta">Teks tombol</label>
                <input id="c-cta" v-model="form.heroCta" class="field" />
              </div>
              <div>
                <label class="field-label" for="c-ctalink">Tautan tombol</label>
                <input id="c-ctalink" v-model="form.heroCtaLink" class="field" />
              </div>
            </div>
          </div>
        </template>

        <template v-else-if="tab === 'announcement'">
          <h2 class="font-display text-2xl">Banner pengumuman</h2>
          <label class="field-label mt-6" for="c-ann">Teks banner</label>
          <input id="c-ann" v-model="form.announcement" class="field" />
          <p class="mt-2 text-[0.78rem] text-muted">Tampil di baris paling atas seluruh halaman toko.</p>
        </template>

        <template v-else-if="tab === 'featured'">
          <h2 class="font-display text-2xl">Produk unggulan</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Empat produk teratas tampil di section “Favorit dari ArafahGift”.</p>
          <ul class="mt-6 divide-y divide-line border-y border-line">
            <li v-for="p in featuredProducts" :key="p.id" class="flex items-center gap-4 py-3.5">
              <GripVertical class="h-4 w-4 flex-none cursor-grab text-muted" />
              <span class="arch h-12 w-9 flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="p.art" :tone="p.id" /></span>
              <span class="flex-1 text-[0.87rem] text-forest">{{ p.name }}</span>
              <button class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger">Keluarkan</button>
            </li>
          </ul>
        </template>

        <template v-else-if="tab === 'giftset'">
          <h2 class="font-display text-2xl">Section gift set</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-sigtitle">Judul</label>
              <input id="c-sigtitle" v-model="form.sigTitle" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-sigbody">Deskripsi</label>
              <textarea id="c-sigbody" v-model="form.sigBody" rows="3" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-sigprod">Produk yang ditampilkan</label>
              <select id="c-sigprod" v-model="form.sigProduct" class="field">
                <option v-for="p in featuredProducts" :key="p.id" :value="p.slug">{{ p.name }}</option>
              </select>
            </div>
          </div>
        </template>

        <template v-else-if="tab === 'testimonial'">
          <h2 class="font-display text-2xl">Testimoni</h2>
          <ul class="mt-6 space-y-4">
            <li v-for="t in testimonials" :key="t.name" class="border border-line p-5">
              <p class="font-display text-[1.1rem] italic leading-snug text-forest">“{{ t.quote }}”</p>
              <p class="mt-3 text-[0.78rem] text-muted">{{ t.name }} · {{ t.city }} · {{ t.rating }} bintang</p>
            </li>
          </ul>
          <AppButton variant="quiet" size="sm" class="mt-5">Tambah testimoni</AppButton>
        </template>

        <template v-else>
          <h2 class="font-display text-2xl">Section Instagram</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="c-ig">Username</label>
              <input id="c-ig" v-model="form.igHandle" class="field" />
            </div>
            <div>
              <label class="field-label" for="c-igurl">Tautan profil</label>
              <input id="c-igurl" v-model="form.igUrl" class="field" />
            </div>
          </div>
        </template>
      </section>

      <aside class="border border-line bg-ivory p-6">
        <p class="text-[0.72rem] uppercase tracking-[0.14em] text-muted">Pratinjau</p>
        <div class="mt-5 border border-line bg-surface p-6">
          <p class="eyebrow">{{ form.heroEyebrow }}</p>
          <p class="mt-4 whitespace-pre-line font-display text-[1.9rem] leading-[1.06] text-forest">{{ form.heroHeadline }}</p>
          <p class="mt-4 text-[0.85rem] leading-relaxed text-muted">{{ form.heroSub }}</p>
          <span class="mt-6 inline-block border border-forest bg-forest px-5 py-2.5 text-[0.8rem] text-ivory">{{ form.heroCta }}</span>
        </div>
        <div class="mt-4 border border-line bg-forest-deep px-4 py-2.5 text-center text-[0.7rem] uppercase tracking-[0.12em] text-ivory/85">
          {{ form.announcement }}
        </div>
      </aside>
    </div>
  </div>
</template>
