<script setup>
import AppRating from '@/components/ui/AppRating.vue'
defineProps({ items: { type: Array, default: () => [] } })
</script>

<template>
  <div class="grid gap-6 md:grid-cols-3">
    <figure
      v-for="(t, i) in items"
      :key="t.name"
      class="flex flex-col rounded-[0.75rem] border border-line bg-surface p-7 shadow-soft"
      :class="i === 0 ? 'md:bg-forest md:text-ivory' : ''"
    >
      <AppRating :value="t.rating" />
      <blockquote
        class="mt-5 flex-1 font-display text-[1.32rem] leading-[1.42]"
        :class="i === 0 ? 'md:text-ivory' : ''"
      >
        “{{ t.quote }}”
      </blockquote>
      <figcaption
        class="mt-7 flex items-center gap-3 border-t pt-4 text-[0.78rem]"
        :class="i === 0 ? 'border-ivory/15 md:text-ivory/70' : 'border-line text-muted'"
      >
        <img v-if="t.avatar" :src="t.avatar" :alt="t.name" class="h-9 w-9 flex-none rounded-full object-cover" loading="lazy" />
        <div class="min-w-0">
          <span class="font-medium" :class="i === 0 ? 'md:text-ivory' : 'text-forest'">{{ t.name }}</span>
          · {{ t.city }}
          <span class="mt-1 block opacity-70">{{ t.context }}</span>
        </div>
      </figcaption>
    </figure>
  </div>
</template>
