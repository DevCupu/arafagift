export function normalizeWhatsAppNumber(value) {
  const digits = String(value ?? '').replace(/\D/g, '')

  if (digits.startsWith('0')) return `62${digits.slice(1)}`
  if (digits.startsWith('8')) return `62${digits}`
  return digits
}

export function buildWhatsAppHref(number, text) {
  const phone = normalizeWhatsAppNumber(number)
  const query = text ? `?text=${encodeURIComponent(text)}` : ''
  return `https://wa.me/${phone}${query}`
}
