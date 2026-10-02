<script setup>
import { computed } from 'vue'

/**
 * Membungkus <img> dengan <picture> supaya browser memilih AVIF atau WebP
 * terkecil yang masih cocok dengan lebar layarnya.
 *
 * srcset sudah disusun di server (App\Support\Image\ResponsiveImage) karena di
 * situ diketahui file mana yang benar-benar ada di disk. Komponen ini tidak
 * menebak-neebak apa pun soal gambar: kalau `image` null atau srcset-nya
 * kosong, yang dirender tetap <img> polos dengan URL fallback.
 *
 * inheritAttrs dimatikan supaya class dari pemanggil menempel ke <img>, bukan
 * ke <picture>. <picture> tidak pernah jadi target layout, jadi
 * absolute inset-0 atau object-cover harus masuk ke elemen gambar.
 */
defineOptions({ inheritAttrs: false })

const props = defineProps({
  // Bentuknya App\Support\Image\ResponsiveImage: { fallback, webp, avif, width, height }
  image: { type: Object, default: null },
  // Dipakai hanya saat `image` null, misal saat tidak ada hero sama sekali.
  src: { type: String, default: '' },
  alt: { type: String, default: '' },
  sizes: { type: String, default: '100vw' },
  // Untuk LCP hero. Element di bawah lipatan dibiarkan lazy.
  priority: { type: Boolean, default: false },
})

const resolvedSrc = computed(() => props.image?.fallback ?? props.src)
const avifSrcset = computed(() => props.image?.avif ?? '')
const webpSrcset = computed(() => props.image?.webp ?? '')
</script>

<template>
  <picture>
    <source v-if="avifSrcset" type="image/avif" :srcset="avifSrcset" :sizes="sizes" />
    <source v-if="webpSrcset" type="image/webp" :srcset="webpSrcset" :sizes="sizes" />
    <img
      v-bind="$attrs"
      :src="resolvedSrc"
      :alt="alt"
      :width="image?.width ?? undefined"
      :height="image?.height ?? undefined"
      :loading="priority ? 'eager' : 'lazy'"
      :fetchpriority="priority ? 'high' : undefined"
      decoding="async"
    />
  </picture>
</template>