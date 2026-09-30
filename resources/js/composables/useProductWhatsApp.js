import { formatIDR } from '@/composables/useFormat'

const absoluteUrl = (path) => {
  if (!path || /^https?:\/\//i.test(path)) return path || null
  if (typeof window === 'undefined') return path

  return new URL(path, window.location.origin).href
}

export function productWhatsAppMessage(product) {
  const productUrl = absoluteUrl(`/produk/${product.slug}`)
  const imageUrl = absoluteUrl(product.image)

  return [
    'Halo ArafahGift, saya ingin memesan produk berikut:',
    '',
    `*${product.name}*`,
    `Harga: ${formatIDR(product.price)}`,
    imageUrl ? `Foto produk: ${imageUrl}` : null,
    `Detail produk: ${productUrl}`,
    '',
    'Mohon info stok dan cara pemesanannya. Terima kasih.',
  ].filter(Boolean).join('\n')
}
