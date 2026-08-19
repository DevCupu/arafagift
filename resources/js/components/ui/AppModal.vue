<script setup>
import { onBeforeUnmount, watch } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({ open: Boolean, label: { type: String, default: 'Dialog' } })
const emit = defineEmits(['close'])

const onKey = (e) => { if (e.key === 'Escape') emit('close') }

watch(
  () => props.open,
  (open) => {
    if (typeof document === 'undefined') return
    document.body.style.overflow = open ? 'hidden' : ''
    open ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey)
  },
)
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  if (typeof document !== 'undefined') document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200" enter-from-class="opacity-0"
      leave-active-class="transition duration-200" leave-to-class="opacity-0"
    >
      <div v-if="open" class="fixed inset-0 z-[60] bg-forest-deep/40 backdrop-blur-[2px]" @click="emit('close')" />
    </Transition>
    <Transition
      enter-active-class="transition duration-300 ease-calm" enter-from-class="translate-y-4 opacity-0 sm:scale-[0.98]"
      leave-active-class="transition duration-200" leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-x-0 bottom-0 z-[61] max-h-[92vh] overflow-y-auto border-t border-line bg-surface sm:inset-0 sm:m-auto sm:h-fit sm:max-w-3xl sm:border"
        role="dialog" :aria-label="label" aria-modal="true"
      >
        <button
          class="absolute right-4 top-4 z-10 grid h-9 w-9 place-items-center border border-line bg-surface text-forest transition hover:bg-ivory"
          aria-label="Tutup" @click="emit('close')"
        >
          <X class="h-4 w-4" />
        </button>
        <slot />
      </div>
    </Transition>
  </Teleport>
</template>
