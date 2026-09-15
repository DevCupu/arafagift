<script setup>
import FaqAccordion from '@/components/storefront/FaqAccordion.vue'

defineProps({ content: { type: Object, required: true } })
</script>

<template>
  <section class="py-16 sm:py-20">
    <div class="shell max-w-[46rem]">
      <h2 v-if="content.title" class="mb-8 text-[1.7rem] sm:text-[2.1rem]">{{ content.title }}</h2>
      <div v-reveal><FaqAccordion :items="content.items ?? []" /></div>
    </div>
  </section>
</template>
