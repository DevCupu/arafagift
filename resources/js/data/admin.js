export const orderStatuses = [
  { id: 'pending', label: 'Menunggu bayar', tone: 'warn' },
  { id: 'paid', label: 'Dibayar', tone: 'info' },
  { id: 'processing', label: 'Diproses', tone: 'info' },
  { id: 'shipped', label: 'Dikirim', tone: 'info' },
  { id: 'completed', label: 'Selesai', tone: 'success' },
  { id: 'cancelled', label: 'Dibatalkan', tone: 'danger' },
]

export const statusMeta = (id) =>
  orderStatuses.find((s) => s.id === id) ?? { id, label: id, tone: 'muted' }

export const paymentStatuses = [
  { id: 'unpaid', label: 'Belum bayar', tone: 'warn' },
  { id: 'waiting_confirmation', label: 'Menunggu verifikasi', tone: 'info' },
  { id: 'paid', label: 'Lunas', tone: 'success' },
  { id: 'refunded', label: 'Refund', tone: 'muted' },
]

// Status yang bisa dipilih admin secara manual. "Menunggu verifikasi" hanya
// muncul dari unggahan bukti pelanggan, jadi tidak ditawarkan sebagai tombol.
export const manualPaymentStatuses = paymentStatuses.filter((s) => s.id !== 'waiting_confirmation')

export const paymentStatusMeta = (id) =>
  paymentStatuses.find((s) => s.id === id) ?? { id, label: id, tone: 'muted' }

// Untuk timeline audit: nilai lama/baru bisa berupa status pesanan maupun
// status pembayaran, jadi cari label di kedua daftar.
export const orderValueLabel = (value) => {
  if (!value) return ''
  return paymentStatuses.find((s) => s.id === value)?.label
    ?? orderStatuses.find((s) => s.id === value)?.label
    ?? value
}

// Kelas badge dipusatkan di sini supaya warna status tidak berbeda antar halaman.
export const paymentPillClass = (id) =>
  ({
    paid: 'border-olive/40 bg-olive/10 text-olive',
    waiting_confirmation: 'border-sky-300 bg-sky-50 text-sky-800',
    refunded: 'border-line bg-ivory text-muted',
  })[id] ?? 'border-gold/45 bg-gold/10 text-forest'

export const fulfillmentMeta = (id) =>
  id === 'pickup'
    ? { id: 'pickup', label: 'Ambil di toko', short: 'Ambil' }
    : { id: 'delivery', label: 'Diantar', short: 'Antar' }

// Label status yang menyesuaikan tipe pemenuhan pesanan.
export const orderStepLabel = (status, fulfillment) => {
  if (fulfillment !== 'pickup') return statusMeta(status).label
  return {
    shipped: 'Siap diambil',
    completed: 'Sudah diambil',
  }[status] ?? statusMeta(status).label
}

export const orderTotal = (order) =>
  Math.max(
    0,
    order.items.reduce((sum, i) => sum + i.price * i.qty, 0)
      - (order.discount ?? 0)
      + order.shipping.cost,
  )
