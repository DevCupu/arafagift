<script setup>
defineProps({ message: String })
</script>

<template>
  <div class="border-b border-forest-deep/15 bg-gold">
    <div class="shell flex h-9 items-center justify-center gap-3 text-[0.62rem] tracking-[0.1em] sm:text-[0.7rem] sm:tracking-[0.12em]">
      <span class="hidden h-1 w-1 rotate-45 bg-forest-deep sm:block" />
      <p class="truncate uppercase text-forest-deep">{{ message }}</p>
      <span class="hidden h-1 w-1 rotate-45 bg-forest-deep sm:block" />
    </div>
  </div>
</template>
