<script setup>
import { Plus } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'
import AppButton from '@/components/ui/AppButton.vue'
import { categories, products } from '@/data/catalog'

const countOf = (slug) => products.filter((p) => p.categorySlug === slug).length
</script>

<template>
  <div>
    <header class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <h1 class="text-[2.1rem] leading-none">Kategori</h1>
        <p class="mt-3 text-[0.85rem] text-muted">Urutan kategori di sini menentukan urutan di homepage.</p>
      </div>
      <AppButton size="sm">
        <template #icon><Plus class="h-3.5 w-3.5" /></template>
        Tambah kategori
      </AppButton>
    </header>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="(c, i) in categories" :key="c.slug" class="flex gap-4 border border-line bg-surface p-4">
        <span class="arch h-24 w-[72px] flex-none overflow-hidden border border-line bg-ivory"><ProductArt :art="c.art" :tone="i" /></span>
        <div class="flex flex-1 flex-col">
          <h2 class="font-display text-xl leading-none text-forest">{{ c.name }}</h2>
          <p class="mt-2 text-[0.78rem] text-muted">/koleksi/{{ c.slug }}</p>
          <p class="mt-1 text-[0.78rem] text-muted">{{ countOf(c.slug) }} produk aktif</p>
          <div class="mt-auto flex gap-3 pt-3">
            <button class="text-[0.78rem] text-forest underline underline-offset-4">Ubah</button>
            <button class="text-[0.78rem] text-muted underline underline-offset-4 hover:text-danger">Hapus</button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>
