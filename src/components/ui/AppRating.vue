<script setup>
import { computed } from 'vue'
import { Star } from 'lucide-vue-next'

const props = defineProps({
  value: { type: Number, default: 5 },
  count: { type: Number, default: null },
  size: { type: String, default: 'sm' }, // sm | md
})
const filled = computed(() => Math.round(props.value))
const dim = computed(() => (props.size === 'md' ? 'h-4 w-4' : 'h-3 w-3'))
</script>

<template>
  <div class="flex items-center gap-1.5" :aria-label="`Rating ${value} dari 5`">
    <div class="flex items-center gap-0.5">
      <Star
        v-for="i in 5"
        :key="i"
        :class="[dim, i <= filled ? 'fill-gold text-gold' : 'text-sand']"
        :stroke-width="1.5"
      />
    </div>
    <span v-if="count !== null" class="text-[0.72rem] text-muted">
      {{ value.toFixed(1) }} <span class="text-muted/70">({{ count }})</span>
    </span>
  </div>
</template>
