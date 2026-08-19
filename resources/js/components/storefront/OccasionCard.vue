<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({ occasion: { type: Object, required: true } })
</script>

<template>
  <Link
    :href="`/koleksi?untuk=${occasion.slug}`"
    class="group flex items-center justify-between gap-4 border border-line bg-surface px-5 py-5 transition duration-300 ease-calm hover:border-olive/50 hover:bg-ivory"
  >
    <div>
      <h3 class="font-display text-[1.22rem] leading-none">{{ occasion.title }}</h3>
      <p class="mt-2 text-[0.78rem] text-muted">{{ occasion.note }}</p>
    </div>
    <span
      class="arch h-12 w-9 flex-none border border-line bg-ivory transition-colors duration-300 group-hover:border-gold/60 group-hover:bg-gold/10"
    />
  </Link>
</template>
