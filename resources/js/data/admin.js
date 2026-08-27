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

export const orderTotal = (order) =>
  order.items.reduce((sum, i) => sum + i.price * i.qty, 0) + order.shipping.cost
