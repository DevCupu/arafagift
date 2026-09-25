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
    <div class="arch aspect-[3/4] overflow-hidden border border-line bg-surface relative transition duration-300 ease-calm group-hover:shadow-lift">
      <div class="h-full w-full transition-transform duration-[900ms] ease-calm group-hover:scale-[1.05]">
        <!-- Foto nyata jika tersedia, fallback ke SVG ilustrasi -->
        <img
          v-if="category.image"
          :src="category.image"
          :alt="category.name"
          loading="lazy"
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
        <h3 class="font-display text-[1.1rem] leading-tight sm:text-[1.35rem]">{{ category.name }}</h3>
        <p v-if="category.tagline" class="mt-1 line-clamp-1 text-[0.72rem] text-muted sm:text-[0.78rem]">{{ category.tagline }}</p>
      </div>
      <span
        class="mt-1 grid h-7 w-7 flex-none place-items-center rounded-full border border-line bg-surface transition duration-300 ease-calm group-hover:border-gold group-hover:bg-gold group-hover:text-forest-deep"
      >
        <ArrowUpRight class="h-3.5 w-3.5" :stroke-width="1.7" />
      </span>
    </div>
  </Link>
</template>
