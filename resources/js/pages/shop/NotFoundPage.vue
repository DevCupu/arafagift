<script setup>
import AppButton from '@/components/ui/AppButton.vue'
</script>

<template>
  <div class="shell flex flex-col items-center py-32 text-center">
    <p class="eyebrow eyebrow--center">404</p>
    <h1 class="mt-6 text-[2.6rem] leading-none sm:text-[3.4rem]">Halaman ini tidak ada.</h1>
    <p class="mt-5 max-w-md text-[0.95rem] text-muted">
      Tautannya mungkin sudah berubah. Mulai lagi dari koleksi — di sana semuanya lengkap.
    </p>
    <div class="mt-8 flex gap-3">
      <AppButton to="/">Ke beranda</AppButton>
      <AppButton to="/koleksi" variant="outline">Lihat koleksi</AppButton>
    </div>
  </div>
</template>
