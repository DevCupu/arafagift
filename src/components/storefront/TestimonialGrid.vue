<script setup>
import AppRating from '@/components/ui/AppRating.vue'
defineProps({ items: { type: Array, default: () => [] } })
</script>

<template>
  <div class="grid gap-6 md:grid-cols-3">
    <figure
      v-for="(t, i) in items"
      :key="t.name"
      class="flex flex-col border border-line bg-surface p-7"
      :class="i === 0 ? 'md:bg-forest md:text-ivory' : ''"
    >
      <AppRating :value="t.rating" />
      <blockquote
        class="mt-5 flex-1 font-display text-[1.32rem] leading-[1.42]"
        :class="i === 0 ? 'md:text-ivory' : ''"
      >
        “{{ t.quote }}”
      </blockquote>
      <figcaption class="mt-7 border-t pt-4 text-[0.78rem]" :class="i === 0 ? 'border-ivory/15 md:text-ivory/70' : 'border-line text-muted'">
        <span class="font-medium" :class="i === 0 ? 'md:text-ivory' : 'text-forest'">{{ t.name }}</span>
        · {{ t.city }}
        <span class="mt-1 block opacity-70">{{ t.context }}</span>
      </figcaption>
    </figure>
  </div>
</template>
