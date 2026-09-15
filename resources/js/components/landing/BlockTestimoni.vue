<script setup>
import { computed } from 'vue'
import TestimonialGrid from '@/components/storefront/TestimonialGrid.vue'

const props = defineProps({ content: { type: Object, required: true } })

// TestimonialGrid dipakai apa adanya: `role` (instansi/jabatan) masuk ke slot
// `city`, rating dipatok 5 karena landing tidak mengumpulkan rating. `avatar`
// (opsional) ikut lewat spread.
const items = computed(() =>
  (props.content.items ?? []).map((t) => ({ ...t, city: t.role, rating: 5 })),
)
</script>

<template>
  <section v-if="items.length" class="py-16 sm:py-20">
    <div class="shell">
      <h2 v-if="content.title" class="mb-10 text-[1.7rem] sm:text-[2.1rem]">{{ content.title }}</h2>
      <div v-reveal><TestimonialGrid :items="items" /></div>
    </div>
  </section>
</template>
