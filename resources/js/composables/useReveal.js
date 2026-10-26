// Scroll reveals only load GSAP on pages that actually use the directive.
// Content remains visible when motion is reduced or the animation cannot load.
export const reveal = {
  mounted(el, binding) {
    const motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)')
    if (motionQuery.matches || el.getBoundingClientRect().top < window.innerHeight * 1.1) return

    let cancelled = false
    let cleanup = null
    el.classList.add('reveal-pending')
    const onMotionChange = (event) => {
      if (event.matches) el._revealCleanup?.()
    }
    motionQuery.addEventListener('change', onMotionChange)

    el._revealCleanup = () => {
      cancelled = true
      motionQuery.removeEventListener('change', onMotionChange)
      cleanup?.()
      el.classList.remove('reveal-pending')
    }

    import('./gsapReveal.js')
      .then(({ animateReveal }) => {
        if (cancelled) return
        // The visitor may have scrolled here while the animation module loaded.
        if (el.getBoundingClientRect().top < window.innerHeight * 0.9) {
          el.classList.remove('reveal-pending')
          return
        }
        cleanup = animateReveal(el, Number(binding.value) || 0)
      })
      .catch(() => el.classList.remove('reveal-pending'))
  },
  unmounted(el) {
    el._revealCleanup?.()
    delete el._revealCleanup
  },
}
