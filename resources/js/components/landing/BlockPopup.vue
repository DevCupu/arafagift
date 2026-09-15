<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { ArrowRight, X } from 'lucide-vue-next'
import { useStore } from '@/composables/useStore'

const props = defineProps({ content: { type: Object, required: true } })
const { whatsappHref } = useStore()

const open = ref(false)
let timer = null

const href = computed(() => props.content.href || whatsappHref('Halo ArafahGift, saya tertarik dengan promo ini.'))

// ponytail: sessionStorage, satu kali per kunjungan — cukup untuk mencegah
// popup muncul berulang tiap reload/scroll di sesi yang sama.
const SEEN_KEY = 'arafagift:popup-seen'

onMounted(() => {
  let alreadySeen = false
  try {
    alreadySeen = Boolean(sessionStorage.getItem(SEEN_KEY))
  } catch {
    // Private mode / storage diblokir: anggap belum pernah lihat, popup tetap tampil.
  }
  if (alreadySeen) return

  const delay = Math.max(0, Number(props.content.delaySeconds) || 4) * 1000
  timer = setTimeout(() => { open.value = true }, delay)
})
onBeforeUnmount(() => clearTimeout(timer))

const close = () => {
  open.value = false
  try {
    sessionStorage.setItem(SEEN_KEY, '1')
  } catch {
    // ignore
  }
}
</script>

<template>
  <Teleport to="body">
    <!-- Kartu kecil melayang, BUKAN modal penutup layar: tanpa backdrop gelap,
         tidak memblokir interaksi dengan halaman di baliknya. -->
    <Transition
      enter-active-class="transition duration-300 ease-calm" enter-from-class="translate-y-4 opacity-0"
      leave-active-class="transition duration-200" leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-x-3 bottom-3 z-[65] mx-auto flex max-w-sm items-start gap-3 rounded border border-line bg-surface p-3 pr-8 shadow-lift sm:inset-x-auto sm:bottom-5 sm:right-5"
        role="dialog" aria-label="Promo"
      >
        <div v-if="content.image" class="arch h-16 w-16 flex-none overflow-hidden">
          <img :src="content.image" alt="" class="h-full w-full object-cover" />
        </div>

        <div class="min-w-0 flex-1">
          <span v-if="content.badge" class="inline-block rounded border border-gold bg-gold px-1.5 py-0.5 text-[0.6rem] font-semibold uppercase tracking-wide text-forest-deep">
            {{ content.badge }}
          </span>
          <p v-if="content.title" class="mt-1 font-display text-[0.88rem] leading-snug text-forest">{{ content.title }}</p>
          <p v-if="content.sub" class="mt-0.5 line-clamp-2 text-[0.72rem] leading-snug text-muted">{{ content.sub }}</p>
          <a
            v-if="content.ctaLabel" :href="href"
            class="link-underline mt-2 inline-flex items-center gap-1 text-[0.76rem] font-medium text-forest"
            @click="close"
          >
            {{ content.ctaLabel }} <ArrowRight class="h-3 w-3" />
          </a>
        </div>

        <button
          type="button" class="absolute right-2 top-2 grid h-6 w-6 place-items-center text-muted transition hover:text-forest"
          aria-label="Tutup" @click="close"
        >
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </Transition>
  </Teleport>
</template>
