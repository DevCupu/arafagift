<script setup>
defineProps({ message: String })
</script>

<template>
  <div class="border-b border-forest-soft/30 bg-forest">
    <div class="shell flex h-9 items-center justify-center gap-3 text-[0.62rem] tracking-[0.1em] sm:text-[0.7rem] sm:tracking-[0.12em]">
      <span class="hidden h-1 w-1 rotate-45 bg-ivory/70 sm:block" />
      <p class="truncate uppercase text-ivory">{{ message }}</p>
      <span class="hidden h-1 w-1 rotate-45 bg-ivory/70 sm:block" />
    </div>
  </div>
</template>
