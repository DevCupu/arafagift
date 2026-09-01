<script setup>
import { computed } from 'vue'

const props = defineProps({ series: { type: Array, required: true } })

const W = 720
const H = 220
const max = computed(() => Math.max(Math.max(...props.series.map((d) => d.value)), 1))
const pts = computed(() =>
  props.series.map((d, i) => ({
    ...d,
    x: (i / (props.series.length - 1)) * W,
    y: H - (d.value / max.value) * H,
  })),
)
const line = computed(() => pts.value.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(1)} ${p.y.toFixed(1)}`).join(' '))
const area = computed(() => `${line.value} L${W} ${H} L0 ${H} Z`)
</script>

<template>
  <div>
    <svg :viewBox="`0 0 ${W} ${H}`" class="h-56 w-full overflow-visible" role="img" aria-label="Grafik penjualan 14 hari terakhir">
      <line v-for="i in 4" :key="i" x1="0" :x2="W" :y1="(H / 4) * (i - 1)" :y2="(H / 4) * (i - 1)" stroke="rgb(var(--c-line))" stroke-width="1" />
      <defs>
        <linearGradient id="agf-fill" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="rgb(var(--c-forest))" stop-opacity="0.16" />
          <stop offset="100%" stop-color="rgb(var(--c-forest))" stop-opacity="0" />
        </linearGradient>
      </defs>
      <path :d="area" fill="url(#agf-fill)" />
      <path :d="line" fill="none" stroke="rgb(var(--c-forest))" stroke-width="1.75" stroke-linejoin="round" stroke-linecap="round" />
      <g>
        <circle
          v-for="(p, i) in pts" :key="i" :cx="p.x" :cy="p.y"
          :r="i === pts.length - 1 ? 5 : 3"
          :fill="i === pts.length - 1 ? 'rgb(var(--c-gold))' : 'rgb(var(--c-surface))'"
          stroke="rgb(var(--c-forest))" stroke-width="1.5"
        />
      </g>
    </svg>
    <div class="mt-3 flex justify-between text-[0.68rem] uppercase tracking-[0.1em] text-muted">
      <span v-for="(d, i) in series" :key="i" :class="i % 2 && i !== series.length - 1 ? 'hidden sm:block' : ''">{{ d.label }}</span>
    </div>
  </div>
</template>
