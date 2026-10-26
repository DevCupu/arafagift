import { onMounted, onUnmounted } from 'vue'
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

// Shared, scoped entrance for the homepage and campaign landing heroes.
export function useHeroMotion(root) {
  let media

  onMounted(() => {
    media = gsap.matchMedia()
    media.add('(prefers-reduced-motion: no-preference)', () => {
      const section = root.value
      if (!section || section.getBoundingClientRect().bottom <= 0) return

      const ctx = gsap.context(() => {
        const image = section.querySelector('.hero-motion-image')
        const eyebrow = section.querySelector('.hero-motion-eyebrow')
        const lines = section.querySelectorAll('.hero-motion-line')
        const rule = section.querySelector('.hero-motion-rule')
        const details = section.querySelectorAll('.hero-motion-detail')
        const timeline = gsap.timeline({ delay: 0.18 })

        if (image) {
          timeline.fromTo(image,
            { scale: 1.09 },
            { scale: 1, duration: 1.9, ease: 'power2.out', clearProps: 'transform' },
            0,
          )
        }
        if (eyebrow) {
          timeline.fromTo(eyebrow,
            { autoAlpha: 0, y: 22 },
            { autoAlpha: 1, y: 0, duration: 0.7, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
            0.12,
          )
        }
        if (lines.length) {
          timeline.fromTo(lines,
            { autoAlpha: 0, yPercent: 110 },
            { autoAlpha: 1, yPercent: 0, duration: 1.05, stagger: 0.16, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
            0.28,
          )
        }
        if (rule) {
          timeline.fromTo(rule,
            { scaleX: 0 },
            { scaleX: 1, duration: 0.85, ease: 'power3.out', clearProps: 'transform' },
            0.7,
          )
        }
        if (details.length) {
          timeline.fromTo(details,
            { autoAlpha: 0, y: 26 },
            { autoAlpha: 1, y: 0, duration: 0.82, stagger: 0.14, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
            0.78,
          )
        }
      }, section)

      return () => ctx.revert()
    })

    media.add('(min-width: 768px) and (prefers-reduced-motion: no-preference)', () => {
      const section = root.value
      const layer = section?.querySelector('.hero-motion-media')
      if (!layer || section.getBoundingClientRect().bottom <= 0) return

      const ctx = gsap.context(() => {
        gsap.to(layer, {
          yPercent: 10,
          ease: 'none',
          scrollTrigger: {
            trigger: section,
            start: 'top top',
            end: 'bottom top',
            scrub: 0.7,
            invalidateOnRefresh: true,
          },
        })
      }, section)

      return () => ctx.revert()
    })
  })

  onUnmounted(() => media?.revert())
}
