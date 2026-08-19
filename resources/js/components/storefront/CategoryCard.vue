<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'

defineProps({
  category: { type: Object, required: true },
  index: { type: Number, default: 0 },
})
</script>

<template>
  <Link :href="`/koleksi/${category.slug}`" class="group block">
    <div class="arch aspect-[3/4] overflow-hidden border border-line bg-surface relative">
      <div class="h-full w-full transition-transform duration-[900ms] ease-calm group-hover:scale-[1.05]">
        <!-- Foto nyata jika tersedia, fallback ke SVG ilustrasi -->
        <img
          v-if="category.image"
          :src="category.image"
          :alt="category.name"
          class="h-full w-full object-cover"
        />
        <ProductArt v-else :art="category.art" :tone="index" />
      </div>
      <!-- Gradient overlay agar nama bisa terbaca di atas foto -->
      <div
        v-if="category.image"
        class="pointer-events-none absolute inset-0 bg-gradient-to-t from-forest-deep/60 via-transparent to-transparent"
      />
    </div>
    <div class="flex items-start justify-between gap-3 pt-3 sm:pt-4">
      <div>
        <h3 class="font-display text-[1.05rem] sm:text-[1.3rem] leading-none">{{ category.name }}</h3>
        <p class="mt-1 sm:mt-1.5 text-[0.72rem] sm:text-[0.78rem] text-muted">{{ category.tagline }}</p>
      </div>
      <ArrowUpRight
        class="mt-1 h-4 w-4 flex-none text-gold transition-transform duration-300 ease-calm group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
      />
    </div>
  </Link>
</template>
