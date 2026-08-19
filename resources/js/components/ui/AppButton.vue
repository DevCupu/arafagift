<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  variant: { type: String, default: 'primary' }, // primary | outline | ghost | gold | quiet
  size: { type: String, default: 'md' }, // sm | md | lg
  to: { type: [String, Object], default: null },
  href: { type: String, default: null },
  type: { type: String, default: 'button' },
  block: Boolean,
  loading: Boolean,
  disabled: Boolean,
})

const base =
  'relative inline-flex select-none items-center justify-center gap-2 rounded border text-center font-medium tracking-wide transition duration-300 ease-calm disabled:cursor-not-allowed disabled:opacity-45'

const variants = {
  primary:
    'border-forest bg-forest text-ivory hover:bg-forest-soft hover:border-forest-soft active:translate-y-px',
  outline:
    'border-forest/25 bg-transparent text-forest hover:border-forest hover:bg-forest hover:text-ivory active:translate-y-px',
  gold: 'border-gold bg-gold text-forest-deep hover:bg-gold/90 active:translate-y-px',
  ghost: 'border-transparent bg-transparent text-forest hover:bg-forest/[0.06]',
  quiet: 'border-line bg-surface text-forest hover:border-olive/50 hover:bg-ivory',
}

const sizes = {
  sm: 'px-3.5 py-2 text-[0.78rem]',
  md: 'px-5 py-3 text-[0.83rem]',
  lg: 'px-7 py-3.5 text-[0.9rem]',
}

const classes = computed(() => [
  base,
  variants[props.variant] ?? variants.primary,
  sizes[props.size] ?? sizes.md,
  props.block ? 'w-full' : '',
])

const tag = computed(() => (props.to ? Link : props.href ? 'a' : 'button'))
</script>

<template>
  <component
    :is="tag"
    :href="to || href || undefined"
    :type="tag === 'button' ? type : undefined"
    :disabled="tag === 'button' ? disabled || loading : undefined"
    :aria-busy="loading || undefined"
    :class="classes"
  >
    <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
    <slot v-else name="icon" />
    <span :class="loading ? 'opacity-70' : ''"><slot /></span>
  </component>
</template>
