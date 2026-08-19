<script setup>
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'

defineProps({ items: { type: Array, default: () => [] } })
const open = ref(0)
const toggle = (i) => { open.value = open.value === i ? -1 : i }
</script>

<template>
  <div class="border-t border-line">
    <div v-for="(item, i) in items" :key="item.q" class="border-b border-line">
      <h3>
        <button
          class="group flex w-full items-start justify-between gap-6 py-5 text-left"
          :aria-expanded="open === i"
          @click="toggle(i)"
        >
          <span class="font-display text-[1.18rem] leading-snug text-forest transition group-hover:text-forest-soft">
            {{ item.q }}
          </span>
          <Plus
            class="mt-1 h-4 w-4 flex-none text-gold transition-transform duration-300 ease-calm"
            :class="open === i ? 'rotate-45' : ''"
          />
        </button>
      </h3>
      <div
        class="grid transition-all duration-300 ease-calm"
        :style="{ gridTemplateRows: open === i ? '1fr' : '0fr' }"
      >
        <div class="overflow-hidden">
          <p class="max-w-2xl pb-6 pr-10 text-[0.9rem] leading-relaxed text-muted">{{ item.a }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
