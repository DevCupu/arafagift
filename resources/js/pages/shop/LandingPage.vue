<script>
// Tanpa nav & footer: landing iklan tidak boleh punya jalan keluar dari funnel.
import BareLayout from '@/layouts/BareLayout.vue'
export default { layout: BareLayout }
</script>

<script setup>
import { Head } from '@inertiajs/vue3'
import { BLOCKS } from '@/components/landing/blocks.js'
import ShareBar from '@/components/landing/ShareBar.vue'

defineProps({
  page: { type: Object, required: true },
  blocks: { type: Array, required: true },
  products: { type: Object, default: () => ({}) },
  seoHead: { type: Object, required: true },
})
</script>

<template>
  <!-- Hanya judul. noindex, nofollow sudah dikirim controller lewat prop seoHead
       sekaligus lewat header X-Robots-Tag, jadi tidak perlu diulang di sini. -->
  <Head :title="seoHead.title" />

  <div class="bg-ivory">
    <div
      v-if="page.status === 'draft'"
      class="bg-gold px-4 py-2 text-center text-[0.78rem] font-medium text-forest-deep"
    >
      Pratinjau draft — halaman ini belum tayang untuk pengunjung.
    </div>

    <component
      :is="BLOCKS[block.type].component"
      v-for="(block, i) in blocks"
      :key="i"
      :content="block.content"
      :products="products"
    />

    <!-- Tetap ada di semua landing, bukan blok yang perlu diatur admin per halaman. -->
    <ShareBar />
  </div>
</template>
