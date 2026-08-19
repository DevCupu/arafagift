<script setup>
import { Instagram } from 'lucide-vue-next'
import ProductArt from '@/components/art/ProductArt.vue'

defineProps({ content: { type: Object, required: true } })
</script>

<template>
  <div>
    <div class="flex flex-wrap items-end justify-between gap-4">
      <div>
        <p class="eyebrow">Instagram</p>
        <h2 class="mt-4 text-[1.9rem] sm:text-[2.3rem]">{{ content.title }}</h2>
      </div>
      <a
        :href="content.url" target="_blank" rel="noopener"
        class="link-underline inline-flex items-center gap-2 text-[0.85rem] text-forest"
      >
        <Instagram class="h-4 w-4 text-gold" />{{ content.handle }}
      </a>
    </div>

    <ul class="mt-10 grid grid-cols-2 gap-px overflow-hidden border border-line bg-line sm:grid-cols-3 lg:grid-cols-6">
      <li v-for="(post, i) in content.posts" :key="i" class="group relative aspect-square bg-surface">
        <ProductArt :art="post.art" :tone="i + 1" />
        <div
          class="absolute inset-0 flex items-end bg-forest-deep/0 p-3 opacity-0 transition duration-500 ease-calm group-hover:bg-forest-deep/55 group-hover:opacity-100"
        >
          <p class="text-[0.72rem] leading-snug text-ivory">{{ post.caption }}</p>
        </div>
      </li>
    </ul>
  </div>
</template>
