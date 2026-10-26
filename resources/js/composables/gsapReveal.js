import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

gsap.registerPlugin(ScrollTrigger)

export function animateReveal(el, delay = 0) {
  const ctx = gsap.context(() => {
    const children = Array.from(el.querySelectorAll('.reveal-child'))
    const timeline = gsap.timeline({ paused: true, delay: Math.max(0, delay) / 1000 })

    timeline.fromTo(el,
      { autoAlpha: 0, y: 22 },
      { autoAlpha: 1, y: 0, duration: 0.78, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
    )

    if (children.length) {
      timeline.fromTo(children,
        { autoAlpha: 0, y: 14 },
        { autoAlpha: 1, y: 0, duration: 0.62, stagger: 0.08, ease: 'power3.out', clearProps: 'opacity,visibility,transform' },
        0.16,
      )
    }

    ScrollTrigger.create({
      trigger: el,
      start: 'top 90%',
      once: true,
      onEnter: () => {
        el.classList.remove('reveal-pending')
        timeline.play()
      },
    })
  }, el)

  return () => ctx.revert()
}
