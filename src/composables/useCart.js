import { computed, ref, watch } from 'vue'
import { products } from '@/data/catalog'
import { useToast } from '@/composables/useToast'

const STORAGE_KEY = 'arafahgift.cart'
const { push } = useToast()

const read = () => {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? JSON.parse(raw) : null
  } catch {
    return null
  }
}

const lines = ref(
  read() ?? [
    { id: 101, qty: 1 },
    { id: 104, qty: 2 },
  ],
)

const drawerOpen = ref(false)
const pulse = ref(0)

watch(
  lines,
  (value) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    } catch {
      /* mode privat: abaikan */
    }
  },
  { deep: true },
)

const items = computed(() =>
  lines.value
    .map((line) => {
      const product = products.find((p) => p.id === line.id)
      return product ? { ...product, qty: line.qty, lineTotal: product.price * line.qty } : null
    })
    .filter(Boolean),
)

const count = computed(() => lines.value.reduce((n, l) => n + l.qty, 0))
const subtotal = computed(() => items.value.reduce((n, i) => n + i.lineTotal, 0))
const savings = computed(() =>
  items.value.reduce(
    (n, i) => n + (i.comparePrice ? (i.comparePrice - i.price) * i.qty : 0),
    0,
  ),
)

export function useCart() {
  const add = (product, qty = 1, { silent = false } = {}) => {
    const existing = lines.value.find((l) => l.id === product.id)
    if (existing) existing.qty += qty
    else lines.value = [...lines.value, { id: product.id, qty }]
    pulse.value++
    if (!silent) push(`${product.name} masuk keranjang`, { tone: 'success' })
  }

  const setQty = (id, qty) => {
    if (qty < 1) return remove(id)
    const line = lines.value.find((l) => l.id === id)
    if (line) line.qty = Math.min(qty, 99)
  }

  const remove = (id) => {
    const gone = items.value.find((i) => i.id === id)
    lines.value = lines.value.filter((l) => l.id !== id)
    if (gone) push(`${gone.name} dihapus dari keranjang`)
  }

  const clear = () => { lines.value = [] }

  const openDrawer = () => { drawerOpen.value = true }
  const closeDrawer = () => { drawerOpen.value = false }

  return {
    items, count, subtotal, savings, drawerOpen, pulse,
    add, setQty, remove, clear, openDrawer, closeDrawer,
  }
}
