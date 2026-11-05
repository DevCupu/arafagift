const CONSENT_KEY = 'arafahgift.analytics-consent'

const config = () => (typeof window === 'undefined' ? {} : (window.__ANALYTICS_CONFIG__ ?? {}))

export const hasAnalytics = () => Boolean(config().ga4 || config().pixel)

export const getConsent = () => {
  try {
    return window.localStorage.getItem(CONSENT_KEY)
  } catch {
    return null
  }
}

export const setConsent = (value) => {
  try {
    window.localStorage.setItem(CONSENT_KEY, value)
  } catch {
    /* mode privat: pilihan tidak bisa disimpan, script tetap tidak dimuat */
  }
}

let loaded = false

/**
 * Memuat GA4 dan Meta Pixel setelah pelanggan memberi izin.
 * Idempoten: aman dipanggil dari banner maupun dari track().
 */
export function loadAnalytics() {
  if (loaded || typeof window === 'undefined') return

  const { ga4, pixel } = config()
  if (!ga4 && !pixel) return

  loaded = true

  if (ga4) {
    window.dataLayer = window.dataLayer || []
    window.gtag = function gtag() {
      window.dataLayer.push(arguments)
    }
    window.gtag('js', new Date())
    window.gtag('config', ga4, { anonymize_ip: true })

    const script = document.createElement('script')
    script.async = true
    script.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga4)}`
    document.head.appendChild(script)
  }

  if (pixel) {
    if (!window.fbq) {
      const fbq = function () {
        fbq.queue.push(arguments)
      }
      fbq.queue = []
      fbq.loaded = true
      fbq.version = '2.0'
      window.fbq = fbq
      window._fbq = fbq
    }

    window.fbq('init', pixel)
    window.fbq('track', 'PageView')

    const script = document.createElement('script')
    script.async = true
    script.src = 'https://connect.facebook.net/en_US/fbevents.js'
    document.head.appendChild(script)
  }
}

/** Dipanggil saat aplikasi mount: lanjutkan analytics kalau izin sudah tersimpan. */
export function initAnalytics() {
  if (getConsent() === 'granted') loadAnalytics()
}

const META_EVENTS = {
  view_item: 'ViewContent',
  add_to_cart: 'AddToCart',
  begin_checkout: 'InitiateCheckout',
  purchase: 'Purchase',
}

/**
 * Kirim event e-commerce ke GA4 dan Meta Pixel.
 * Tidak melakukan apa pun sebelum pelanggan menyetujui cookie analitik.
 */
export function track(event, payload = {}) {
  if (getConsent() !== 'granted') return

  loadAnalytics()

  if (window.gtag) {
    window.gtag('event', event, payload)
  }

  const metaEvent = META_EVENTS[event]
  if (!metaEvent || !window.fbq) return

  const items = Array.isArray(payload.items) ? payload.items : []
  const metaPayload = {
    value: payload.value,
    currency: payload.currency ?? 'IDR',
    content_ids: items.map((item) => item.item_id).filter(Boolean),
    content_type: 'product',
    contents: items.map((item) => ({
      id: item.item_id,
      quantity: item.quantity ?? 1,
      item_price: item.price,
    })),
    num_items: items.reduce((total, item) => total + (item.quantity ?? 1), 0),
  }

  if (payload.transaction_id) {
    metaPayload.order_id = payload.transaction_id
  }

  window.fbq('track', metaEvent, metaPayload)
}
