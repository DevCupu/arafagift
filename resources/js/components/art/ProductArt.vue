<script setup>
import { computed } from 'vue'

/**
 * ProductArt — placeholder editorial "photography".
 * Setiap produk punya motif garis tipis di atas wash warna brand.
 * Ganti component ini dengan <img> asli saat foto produk sudah tersedia:
 * struktur wrapper & aspect ratio-nya sudah sama.
 */
const props = defineProps({
  art: { type: String, default: 'giftset' },
  tone: { type: Number, default: 0 },
  dark: Boolean, // dipakai saat motif berada di atas latar hijau tua
})

const tones = [
  { bg: 'rgb(var(--c-sand))', halo: 'rgb(var(--c-gold) / 0.5)' },
  { bg: 'rgb(var(--c-ivory))', halo: 'rgb(var(--c-olive) / 0.38)' },
  { bg: 'rgb(var(--c-olive) / 0.18)', halo: 'rgb(var(--c-ivory) / 0.9)' },
  { bg: 'rgb(var(--c-gold) / 0.13)', halo: 'rgb(var(--c-forest) / 0.2)' },
]

const t = computed(() =>
  props.dark
    ? { bg: 'rgb(var(--c-forest-deep))', halo: 'rgb(var(--c-forest-soft) / 0.9)' }
    : tones[Math.abs(props.tone) % tones.length],
)
const cMain = computed(() => (props.dark ? 'rgb(var(--c-ivory) / 0.5)' : 'rgb(var(--c-forest) / 0.55)'))
const cAlt = computed(() => (props.dark ? 'rgb(var(--c-gold-soft) / 0.7)' : 'rgb(var(--c-olive))'))
const cBase = computed(() => (props.dark ? 'rgb(var(--c-ivory) / 0.18)' : 'rgb(var(--c-forest) / 0.16)'))
const haloId = `agf-halo-${Math.random().toString(36).slice(2, 8)}`

const beads = Array.from({ length: 26 }, (_, i) => {
  const a = (i / 26) * Math.PI * 2 - Math.PI / 2
  return { x: 200 + Math.cos(a) * 78, y: 250 + Math.sin(a) * 78, r: i % 13 === 0 ? 7 : 5 }
})
</script>

<template>
  <svg
    class="h-full w-full"
    viewBox="0 0 400 500"
    preserveAspectRatio="xMidYMid slice"
    role="img"
    aria-hidden="true"
  >
    <defs>
      <radialGradient :id="haloId" cx="50%" cy="42%" r="58%">
        <stop offset="0%" :stop-color="t.halo" stop-opacity="1" />
        <stop offset="70%" :stop-color="t.halo" stop-opacity="0.45" />
        <stop offset="100%" :stop-color="t.halo" stop-opacity="0" />
      </radialGradient>
    </defs>
    <rect width="400" height="500" :fill="t.bg" />
    <rect width="400" height="500" :fill="`url(#${haloId})`" />

    <g
      transform="translate(200 268) scale(1.16) translate(-200 -268)"
      fill="none"
      :stroke="cMain"
      stroke-width="1.25"
      stroke-linecap="round"
      stroke-linejoin="round"
    >
      <!-- Gift set: box + pita + tag -->
      <template v-if="art === 'giftset'">
        <path d="M112 214h176v146H112z" />
        <path d="M100 182h200v34H100z" />
        <path d="M182 182v178M218 182v178" stroke="rgb(var(--c-gold) / 0.85)" />
        <path d="M200 182c-16-30-52-32-52-8 0 14 26 12 52 8zM200 182c16-30 52-32 52-8 0 14-26 12-52 8z" />
        <path d="M262 300h44M262 300l-8-30" stroke="rgb(var(--c-gold) / 0.7)" />
        <circle cx="312" cy="300" r="16" stroke="rgb(var(--c-gold) / 0.7)" />
      </template>

      <!-- Kurma: mangkuk + buah + pelepah -->
      <template v-else-if="art === 'kurma'">
        <path d="M104 262h192c0 60-43 96-96 96s-96-36-96-96z" />
        <path d="M92 262h216" stroke="rgb(var(--c-gold) / 0.8)" />
        <ellipse cx="164" cy="234" rx="20" ry="30" transform="rotate(-16 164 234)" />
        <ellipse cx="206" cy="222" rx="20" ry="31" transform="rotate(4 206 222)" />
        <ellipse cx="248" cy="236" rx="19" ry="29" transform="rotate(18 248 236)" />
        <ellipse cx="186" cy="252" rx="19" ry="28" transform="rotate(-6 186 252)" />
        <ellipse cx="228" cy="254" rx="19" ry="28" transform="rotate(10 228 254)" />
        <path d="M200 190c34-22 62-18 78-4" :stroke="cAlt" />
        <path d="M240 178l-6-16M258 180l2-18M276 186l10-14" :stroke="cAlt" />
      </template>

      <!-- Sajadah: mihrab + rumbai -->
      <template v-else-if="art === 'sajadah'">
        <path d="M124 366V246c0-42 34-76 76-76s76 34 76 76v120z" />
        <path d="M146 366V250c0-30 24-54 54-54s54 24 54 54v116" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M170 366V256c0-17 13-30 30-30s30 13 30 30v110" />
        <path d="M124 366h152" />
        <path
          d="M136 376v20M156 376v20M176 376v20M196 376v20M216 376v20M236 376v20M256 376v20"
          stroke="rgb(var(--c-gold) / 0.6)"
        />
        <path d="M200 244v18" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M188 262h24l-6 26h-12z" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M194 296h12" stroke="rgb(var(--c-gold) / 0.8)" />
      </template>

      <!-- Tasbih: lingkaran butir + rumbai -->
      <template v-else-if="art === 'tasbih'">
        <circle
          v-for="(b, i) in beads"
          :key="i"
          :cx="b.x"
          :cy="b.y"
          :r="b.r"
          :stroke="i % 13 === 0 ? 'rgb(var(--c-gold))' : cMain"
        />
        <path d="M200 172v-38" stroke="rgb(var(--c-gold))" />
        <path d="M190 128h20l-6 26h-8z" stroke="rgb(var(--c-gold))" />
        <path d="M186 108h28" stroke="rgb(var(--c-gold))" />
      </template>

      <!-- Madu: toples + sarang -->
      <template v-else-if="art === 'madu'">
        <path d="M150 214h100v146a12 12 0 0 1-12 12h-76a12 12 0 0 1-12-12z" />
        <path d="M142 196h116v22H142z" stroke="rgb(var(--c-gold) / 0.85)" />
        <path d="M150 286c26-14 74-14 100 0" stroke="rgb(var(--c-gold) / 0.85)" />
        <path d="M276 176l-14 78c-3 16 8 26 20 20" :stroke="cAlt" />
        <path
          d="M300 300l14 8v16l-14 8-14-8v-16zM330 316l14 8v16l-14 8-14-8v-16z"
          stroke="rgb(var(--c-gold) / 0.55)"
        />
      </template>

      <!-- Parfum: botol attar -->
      <template v-else-if="art === 'parfum'">
        <path d="M164 228h72v118a14 14 0 0 1-14 14h-44a14 14 0 0 1-14-14z" />
        <path d="M182 196h36v32h-36z" />
        <path d="M176 176h48l-8 20h-32z" stroke="rgb(var(--c-gold) / 0.9)" />
        <path d="M188 152c0 12 24 12 24 0 0-10-12-18-12-18s-12 8-12 18z" stroke="rgb(var(--c-gold) / 0.9)" />
        <path d="M180 268h40M180 292h40" stroke="rgb(var(--c-gold) / 0.6)" />
      </template>

      <!-- Souvenir rombongan: tiga pouch -->
      <template v-else-if="art === 'souvenir'">
        <path d="M96 262h84v104H96zM158 262h84v104h-84zM220 262h84v104h-84z" opacity="0" />
        <path d="M104 268h72l10 98h-92zM164 268h72l10 98h-92zM224 268h72l10 98h-92" />
        <path d="M118 268c0-22 14-34 32-34s32 12 32 34" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M178 268c0-22 14-34 32-34s32 12 32 34" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M238 268c0-22 14-34 32-34s32 12 32 34" stroke="rgb(var(--c-gold) / 0.8)" />
        <path d="M96 268h216" />
      </template>

      <!-- Kacang / default -->
      <template v-else>
        <path d="M128 250h144l-14 116H142z" />
        <path d="M128 250c0-26 32-42 72-42s72 16 72 42" stroke="rgb(var(--c-gold) / 0.8)" />
        <ellipse cx="176" cy="296" rx="14" ry="11" transform="rotate(-14 176 296)" />
        <ellipse cx="214" cy="288" rx="14" ry="11" transform="rotate(8 214 288)" />
        <ellipse cx="196" cy="322" rx="14" ry="11" transform="rotate(-4 196 322)" />
      </template>
    </g>

    <!-- garis alas: menjaga komposisi tetap "editorial" -->
    <path d="M60 412h280" :stroke="cBase" stroke-width="1" />
  </svg>
</template>
