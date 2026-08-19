const rupiah = new Intl.NumberFormat('id-ID', {
  style: 'currency',
  currency: 'IDR',
  minimumFractionDigits: 0,
  maximumFractionDigits: 0,
})

export const formatIDR = (value) => rupiah.format(value ?? 0)

export const formatShort = (value) => {
  if (value >= 1000000) return `Rp ${(value / 1000000).toFixed(1).replace('.', ',')} jt`
  if (value >= 1000) return `Rp ${Math.round(value / 1000)} rb`
  return formatIDR(value)
}

export const formatDate = (value, opts = {}) =>
  new Date(value).toLocaleDateString('id-ID', {
    day: 'numeric', month: 'short', year: 'numeric', ...opts,
  })

export const formatDateTime = (value) =>
  new Date(value).toLocaleString('id-ID', {
    day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
  })
