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
  { id: 'paid', label: 'Lunas', tone: 'success' },
  { id: 'refunded', label: 'Refund', tone: 'muted' },
]

export const paymentStatusMeta = (id) =>
  paymentStatuses.find((s) => s.id === id) ?? { id, label: id, tone: 'muted' }

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
