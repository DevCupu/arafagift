<script setup>
defineProps({ message: String })
</script>

<template>
  <div class="border-b border-forest-soft/40 bg-forest-deep text-ivory/85">
    <div class="shell flex h-9 items-center justify-center gap-3 text-[0.62rem] tracking-[0.1em] sm:text-[0.7rem] sm:tracking-[0.12em]">
      <span class="hidden h-1 w-1 rotate-45 bg-gold sm:block" />
      <p class="truncate uppercase">{{ message }}</p>
      <span class="hidden h-1 w-1 rotate-45 bg-gold sm:block" />
    </div>
  </div>
</template>
