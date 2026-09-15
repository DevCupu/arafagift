<script setup>
import { ref } from 'vue'
import { Check, Facebook, Link2, MessageCircle } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { push } = useToast()
const copied = ref(false)

// Dibaca saat klik (bukan computed), supaya selalu pakai URL & query string
// yang sedang aktif di address bar, termasuk UTM dari iklan.
const shareUrl = () => (typeof window !== 'undefined' ? window.location.href : '')
const waHref = () => `https://wa.me/?text=${encodeURIComponent(shareUrl())}`
const fbHref = () => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl())}`

const copyLink = async () => {
  try {
    await navigator.clipboard.writeText(shareUrl())
  } catch {
    // ponytail: fallback textarea+execCommand untuk browser lama / clipboard API diblokir.
    const input = document.createElement('textarea')
    input.value = shareUrl()
    input.style.position = 'fixed'
    input.style.opacity = '0'
    document.body.appendChild(input)
    input.select()
    document.execCommand('copy')
    document.body.removeChild(input)
  }
  copied.value = true
  push('Link disalin', { tone: 'success' })
  setTimeout(() => { copied.value = false }, 2000)
}
</script>

<template>
  <section class="border-t border-line py-10 sm:py-12" v-reveal>
    <div class="shell flex flex-col items-center gap-4 text-center">
      <p class="text-[0.78rem] text-muted">Bagikan halaman ini</p>
      <div class="flex items-center gap-3">
        <a
          :href="waHref()" target="_blank" rel="noopener" aria-label="Bagikan ke WhatsApp"
          class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition duration-300 ease-calm hover:border-forest hover:bg-forest hover:text-ivory"
        >
          <MessageCircle class="h-4 w-4" :stroke-width="1.5" />
        </a>
        <a
          :href="fbHref()" target="_blank" rel="noopener" aria-label="Bagikan ke Facebook"
          class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition duration-300 ease-calm hover:border-forest hover:bg-forest hover:text-ivory"
        >
          <Facebook class="h-4 w-4" :stroke-width="1.5" />
        </a>
        <button
          type="button" aria-label="Salin link" @click="copyLink"
          class="grid h-10 w-10 place-items-center rounded-full border border-line text-forest transition duration-300 ease-calm hover:border-forest hover:bg-forest hover:text-ivory"
        >
          <Check v-if="copied" class="h-4 w-4" :stroke-width="1.5" />
          <Link2 v-else class="h-4 w-4" :stroke-width="1.5" />
        </button>
      </div>
    </div>
  </section>
</template>
