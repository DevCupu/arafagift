<script setup>
import { Check, X } from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'

const { toasts, dismiss } = useToast()
</script>

<template>
  <div class="pointer-events-none fixed inset-x-0 bottom-0 z-[70] flex flex-col items-center gap-2 p-4 sm:items-end sm:p-6">
    <TransitionGroup
      enter-active-class="transition duration-300 ease-calm"
      enter-from-class="translate-y-3 opacity-0"
      leave-active-class="transition duration-200"
      leave-to-class="translate-y-1 opacity-0"
    >
      <div
        v-for="t in toasts"
        :key="t.id"
        role="status"
        class="pointer-events-auto flex w-full max-w-sm items-center gap-3 rounded border border-forest/15 bg-forest px-4 py-3 text-ivory shadow-lift"
      >
        <Check v-if="t.tone === 'success'" class="h-4 w-4 flex-none text-gold" />
        <p class="flex-1 text-[0.83rem] leading-snug">{{ t.message }}</p>
        <button class="text-ivory/60 transition hover:text-ivory" aria-label="Tutup" @click="dismiss(t.id)">
          <X class="h-3.5 w-3.5" />
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>
