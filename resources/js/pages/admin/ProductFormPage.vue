<script>
import AdminLayout from '@/layouts/AdminLayout.vue'
export default { layout: AdminLayout }
</script>

<script setup>
import { reactive, ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ArrowLeft, ImagePlus } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { categories, findProduct } from '@/data/catalog'
import { useToast } from '@/composables/useToast'

const props = defineProps({ slug: { type: String, default: null } })
const { push } = useToast()
const existing = props.slug ? findProduct(props.slug) : null
const saving = ref(false)

const form = reactive({
  name: existing?.name ?? '',
  slug: existing?.slug ?? '',
  short: existing?.short ?? '',
  description: existing?.description ?? '',
  category: existing?.categorySlug ?? 'gift-set',
  price: existing?.price ?? '',
  comparePrice: existing?.comparePrice ?? '',
  cost: existing?.cost ?? '',
  stock: existing?.stock ?? 0,
  lowStock: existing?.lowStock ?? 10,
  sku: existing?.sku ?? '',
  weight: existing?.weight ?? '',
  status: existing?.status ?? 'draft',
  featured: existing?.featured ?? false,
  seoTitle: existing ? `${existing.name} — ArafahGift.id` : '',
  seoDescription: existing?.short ?? '',
})

const slugify = () => {
  if (!form.slug) form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
}

const save = () => {
  saving.value = true
  setTimeout(() => {
    saving.value = false
    push(existing ? 'Perubahan produk disimpan' : 'Produk baru dibuat sebagai draft', { tone: 'success' })
  }, 800)
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
        <AppButton variant="quiet" size="sm">Pratinjau</AppButton>
        <AppButton size="sm" :loading="saving" @click="save">Simpan produk</AppButton>
      </div>
    </header>

    <div class="mt-8 grid gap-6 xl:grid-cols-[1.6fr_1fr]">
      <div class="space-y-6">
        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Informasi dasar</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="f-name">Nama produk</label>
              <input id="f-name" v-model="form.name" class="field" placeholder="Contoh: Arafah Premium Box" @blur="slugify" />
            </div>
            <div>
              <label class="field-label" for="f-slug">Slug URL</label>
              <div class="flex items-center gap-2">
                <span class="text-[0.8rem] text-muted">arafahgift.id/produk/</span>
                <input id="f-slug" v-model="form.slug" class="field" placeholder="arafah-premium-box" />
              </div>
            </div>
            <div>
              <label class="field-label" for="f-short">Deskripsi singkat</label>
              <input id="f-short" v-model="form.short" class="field" placeholder="Satu kalimat yang muncul di kartu produk" />
            </div>
            <div>
              <label class="field-label" for="f-desc">Deskripsi lengkap</label>
              <textarea id="f-desc" v-model="form.description" rows="6" class="field" />
            </div>
          </div>
        </section>

        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Foto produk</h2>
          <p class="mt-2 text-[0.83rem] text-muted">Foto pertama dipakai sebagai gambar utama. Rasio 4:5, minimal 1200 px.</p>
          <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div v-for="i in 3" :key="i" class="arch aspect-[4/5] overflow-hidden border border-line bg-ivory">
              <ProductArt :art="existing?.art ?? 'giftset'" :tone="i" />
            </div>
            <button class="arch flex aspect-[4/5] flex-col items-center justify-center gap-2 border border-dashed border-line bg-ivory/60 text-muted transition hover:border-olive/60 hover:text-forest">
              <ImagePlus class="h-5 w-5 text-gold" :stroke-width="1.4" />
              <span class="text-[0.75rem]">Unggah</span>
            </button>
          </div>
        </section>

        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">Harga</h2>
          <div class="mt-6 grid gap-5 sm:grid-cols-3">
            <div>
              <label class="field-label" for="f-price">Harga jual</label>
              <input id="f-price" v-model="form.price" inputmode="numeric" class="field" placeholder="649000" />
            </div>
            <div>
              <label class="field-label" for="f-compare">Harga coret</label>
              <input id="f-compare" v-model="form.comparePrice" inputmode="numeric" class="field" placeholder="749000" />
            </div>
            <div>
              <label class="field-label" for="f-cost">Harga modal</label>
              <input id="f-cost" v-model="form.cost" inputmode="numeric" class="field" placeholder="410000" />
            </div>
          </div>
        </section>

        <section class="border border-line bg-surface p-6 sm:p-7">
          <h2 class="font-display text-2xl">SEO</h2>
          <div class="mt-6 space-y-5">
            <div>
              <label class="field-label" for="f-seo-title">Judul halaman</label>
              <input id="f-seo-title" v-model="form.seoTitle" class="field" />
            </div>
            <div>
              <label class="field-label" for="f-seo-desc">Meta description</label>
              <textarea id="f-seo-desc" v-model="form.seoDescription" rows="3" class="field" />
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
          <select id="f-cat" v-model="form.category" class="field">
            <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
          </select>
        </section>

        <section class="border border-line bg-surface p-6">
          <h2 class="font-display text-2xl">Inventori</h2>
          <div class="mt-5 space-y-5">
            <div>
              <label class="field-label" for="f-sku">SKU</label>
              <input id="f-sku" v-model="form.sku" class="field" placeholder="AGF-BOX-01" />
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="field-label" for="f-stock">Stok</label>
                <input id="f-stock" v-model="form.stock" inputmode="numeric" class="field" />
              </div>
              <div>
                <label class="field-label" for="f-low">Batas menipis</label>
                <input id="f-low" v-model="form.lowStock" inputmode="numeric" class="field" />
              </div>
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
