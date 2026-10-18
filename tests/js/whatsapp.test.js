import test from 'node:test'
import assert from 'node:assert/strict'
import { buildWhatsAppHref } from '../../resources/js/composables/whatsapp.js'

test('nomor lokal pengaturan menghasilkan tautan WhatsApp internasional', () => {
  assert.equal(buildWhatsAppHref('08192242444'), 'https://wa.me/628192242444')
  assert.equal(buildWhatsAppHref('8192242444'), 'https://wa.me/628192242444')
})

test('nomor internasional dan pesan terisi tetap benar', () => {
  assert.equal(
    buildWhatsAppHref('+62 819-2242-444', 'Halo ArafahGift, saya mau pesan.'),
    'https://wa.me/628192242444?text=Halo%20ArafahGift%2C%20saya%20mau%20pesan.',
  )
  assert.equal(buildWhatsAppHref('628192242444'), 'https://wa.me/628192242444')
})
