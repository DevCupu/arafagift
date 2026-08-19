<script setup>
defineProps({
  title: { type: String, default: 'Belum ada apa-apa di sini' },
  body: String,
})
</script>

<template>
  <div class="flex flex-col items-center border border-dashed border-line bg-surface px-6 py-16 text-center">
    <div class="arch flex h-16 w-12 items-end justify-center border border-line bg-ivory pb-2">
      <span class="h-1.5 w-1.5 rounded-full bg-gold/70" />
    </div>
    <h3 class="mt-6 text-xl">{{ title }}</h3>
    <p v-if="body" class="mt-2 max-w-sm text-sm text-muted">{{ body }}</p>
    <div class="mt-6"><slot /></div>
  </div>
</template>
