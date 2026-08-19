// v-reveal: fade-up saat elemen masuk viewport.
// Elemen yang sudah terlihat saat mount (atau saat halaman dibuka dengan
// posisi scroll tertentu) langsung tampil tanpa animasi, sehingga tidak ada
// konten yang "hilang" ketika pengguna melompat ke tengah halaman.
export const reveal = {
  mounted(el, binding) {
    const reduced =
      typeof window !== 'undefined' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches

    if (typeof IntersectionObserver === 'undefined' || reduced) {
      el.classList.add('is-in')
      return
    }

    el.classList.add('reveal')
    if (binding.value) el.style.transitionDelay = `${binding.value}ms`

    const show = () => {
      el.classList.add('is-in')
      el.style.transitionDelay = ''
    }

    // Sudah berada di viewport saat dipasang → tampilkan segera.
    if (el.getBoundingClientRect().top < window.innerHeight * 1.1) {
      requestAnimationFrame(show)
    }

    const io = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) { show(); io.disconnect() }
      },
      { threshold: 0.08, rootMargin: '0px 0px -40px 0px' },
    )
    io.observe(el)
    el._io = io
  },
  unmounted(el) { el._io && el._io.disconnect() },
}
