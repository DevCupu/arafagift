<script setup>
import { Link } from '@inertiajs/vue3'
import { ArrowUpRight } from 'lucide-vue-next'

defineProps({ occasion: { type: Object, required: true } })
</script>

<template>
  <Link
    :href="`/koleksi?untuk=${occasion.slug}`"
    class="group flex items-center justify-between gap-4 border border-line bg-surface px-5 py-5 transition duration-300 ease-calm hover:border-olive/50 hover:bg-ivory"
  >
    <div class="min-w-0">
      <h3 class="font-display text-[1.22rem] leading-none">{{ occasion.title }}</h3>
      <p v-if="occasion.note" class="mt-1.5 text-[0.78rem] text-muted">{{ occasion.note }}</p>
    </div>
    <span
      class="arch flex h-12 w-9 flex-none items-center justify-center border border-line bg-ivory transition duration-300 ease-calm group-hover:border-gold/60 group-hover:bg-gold/10"
      aria-hidden="true"
    >
      <ArrowUpRight
        class="h-4 w-4 text-gold transition-transform duration-300 ease-calm group-hover:-translate-y-0.5 group-hover:translate-x-0.5"
      />
    </span>
  </Link>
</template>
